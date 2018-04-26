<?php

namespace Drupal\media_entity_instagram;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Utility\Error;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Fetches instagram post via oembed.
 *
 * Fetches (and caches) instagram post data from free to use Instagram's oEmbed
 * call.
 */
class InstagramEmbedFetcher implements InstagramEmbedFetcherInterface {

  const INSTAGRAM_URL = 'http://instagr.am/p/';

  const INSTAGRAM_API = 'http://api.instagram.com/oembed';

  /**
   * The optional cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * InstagramEmbedFetcher constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   A HTTP Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   A logger factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface|null $cache
   *   (optional) A cache bin for storing fetched instagram posts.
   */
  public function __construct(Client $client, LoggerChannelFactoryInterface $loggerFactory, CacheBackendInterface $cache = NULL) {
    $this->httpClient = $client;
    $this->loggerFactory = $loggerFactory;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchInstagramEmbed($shortcode, $hidecaption = FALSE, $maxWidth = NULL) {

    $options = [
      'url' => self::INSTAGRAM_URL . $shortcode . '/',
      'hidecaption' => (int) $hidecaption,
      'omitscript' => 1,
    ];

    if ($maxWidth) {
      $options['maxwidth'] = $maxWidth;
    }

    // Tweets don't change much, so pull it out of the cache (if we have one)
    // if this one has already been fetched.
    $cacheKey = md5(serialize($options));
    if ($this->cache && $cached_instagram_post = $this->cache->get($cacheKey)) {
      return $cached_instagram_post->data;
    }

    $queryParameter = UrlHelper::buildQuery($options);

    try {
      $response = $this->httpClient->request(
        'GET',
        self::INSTAGRAM_API . '?' . $queryParameter,
        ['timeout' => 5]
      );
      if ($response->getStatusCode() === 200) {
        $data = Json::decode($response->getBody()->getContents());
      }
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('media_entity_instagram')->error("Could not retrieve Instagram post $shortcode.", Error::decodeException($e));
    }

    // If we got data from Instagram oEmbed request, return data.
    if (isset($data)) {

      // If we have a cache, store the response for future use.
      if ($this->cache) {
        // Instagram posts don't change often, so the response should expire
        // from the cache on its own in 90 days.
        $this->cache->set($cacheKey, $data, time() + (86400 * 90));
      }

      return $data;
    }
    return FALSE;
  }

}
