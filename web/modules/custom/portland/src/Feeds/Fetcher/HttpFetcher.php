<?php

namespace Drupal\portland\Feeds\Fetcher;

use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Result\HttpFetcherResult;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\Response;

use Drupal\feeds\Feeds\Fetcher\HttpFetcher as FeedsFetcher;

/**
 * Defines an HTTP fetcher.
 *
 * @FeedsFetcher(
 *   id = "http_portland",
 *   title = @Translation("Portland feeds fetcher"),
 *   description = @Translation("Downloads data from a URL using Drupal's HTTP request handler with custom headers."),
 *   form = {
 *     "configuration" = "Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherForm",
 *     "feed" = "Drupal\feeds\Feeds\Fetcher\Form\HttpFetcherFeedForm",
 *   }
 * )
 */
class HttpFetcher extends FeedsFetcher {
  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $sink = $this->fileSystem->tempnam('temporary://', 'feeds_http_fetcher');
    $sink = $this->fileSystem->realpath($sink);

    $response = $this->getFeed($feed, $sink, $this->getCacheKey($feed));
    // @todo Handle redirects.
    // @codingStandardsIgnoreStart
    // $feed->setSource($response->getEffectiveUrl());
    // @codingStandardsIgnoreEnd

    // 304, nothing to see here.
    if ($response->getStatusCode() == Response::HTTP_NOT_MODIFIED) {
      $state->setMessage($this->t('The feed has not been updated.'));
      throw new EmptyFeedException();
    }

    return new HttpFetcherResult($sink, $response->getHeaders());
  }

  /**
   * {@inheritdoc}
   *
   * Override the Feeds fetcher to add custom headers
   */
  protected function getFeed($feed, $sink, $cache_key = FALSE) {
    $url = $feed->getSource();
    $url = Feed::translateSchemes($url);

    // Append query string if this is the first import from Synergy
    if($feed->getType()->id() === "synergy_json_feed") {
      $last_request_date = \Drupal::state()->get("feed.".$feed->id().".last_request_date");
      // Synergy expects dateFrom and dateTo (optional) query strings
      // If this is the first import, set a default initial date
      if( empty($last_request_date) ) {
        $url .= "?dateFrom=".date('Y-m-d', strtotime( '-1 years' ));
      }
      else {
        $url .= "?dateFrom=".$last_request_date;
      }
    }

    // Add the authorization header if the feed field is set
    $options = [RequestOptions::SINK => $sink];
    if($feed->hasField('field_authorization_header')) {
      $auth_header = $feed->field_authorization_header->value;
      if(!empty($auth_header)) {
        $options[RequestOptions::HEADERS]['Authorization'] = $auth_header;
      }
    }

    // Add cached headers if requested.
    if ($cache_key && ($cache = $this->cache->get($cache_key))) {
      if (isset($cache->data['etag'])) {
        $options[RequestOptions::HEADERS]['If-None-Match'] = $cache->data['etag'];
      }
      if (isset($cache->data['last-modified'])) {
        $options[RequestOptions::HEADERS]['If-Modified-Since'] = $cache->data['last-modified'];
      }
    }

    try {
      $response = $this->client->get($url, $options);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }

    if ($cache_key) {
      $this->cache->set($cache_key, array_change_key_case($response->getHeaders()));
    }

    // Set it to yesterday to make sure there is no gap
    \Drupal::state()->set("feed.".$feed->id().".last_request_date", date('Y-m-d', strtotime( '-1 days' )));

    return $response;
  }

}
