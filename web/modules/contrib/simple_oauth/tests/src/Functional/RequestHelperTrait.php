<?php

namespace Drupal\Tests\simple_oauth\Functional;

use Drupal\Core\Url;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

trait RequestHelperTrait {

  /**
   * Performs a HTTP request. Wraps the Guzzle HTTP client.
   *
   * Why wrap the Guzzle HTTP client? Because any error response is returned via
   * an exception, which would make the tests unnecessarily complex to read.
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function request($method, Url $url, array $request_options) {
    try {
      $response = $this->httpClient->request($method, $url->toString(), $request_options);
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
    }
    catch (ServerException $e) {
      $response = $e->getResponse();
    }
    catch (RequestException $e) {
      $response = $e->getResponse();
    }

    return $response;
  }

}
