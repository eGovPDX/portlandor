<?php

namespace Drupal\jsonapi\StackMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Sets the 'api_json' format on all requests to JSON API-managed routes.
 *
 * @internal
 */
class FormatSetter implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a FormatSetter object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if (static::isJsonApiRequest($request)) {
      $request->setRequestFormat('api_json');
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Checks whether the current request is a JSON API request.
   *
   * Inspects:
   * - request path (uses a heuristic, because e.g. language negotiation may use
   *   path prefixes)
   * - 'Accept' request header value.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return bool
   *   Whether the current request is a JSON API request.
   */
  protected static function isJsonApiRequest(Request $request) {
    return strpos($request->getPathInfo(), '/jsonapi/') !== FALSE
      &&
      // Check if the 'Accept' header includes the JSON API MIME type.
      count(array_filter($request->getAcceptableContentTypes(), function ($accept) {
        return strpos($accept, 'application/vnd.api+json') === 0;
      }));
  }

}
