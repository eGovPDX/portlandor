<?php

namespace Drupal\simple_oauth\HttpMiddleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Uses the basic auth information to provide the client credentials for OAuth2.
 */
class BasicAuthSwap implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a BasicAuthSwap object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * Handles a Request to convert it to a Response.
   *
   * If the request appears to be an OAuth2 token request with Basic Auth,
   * swap the Basic Auth credentials into the request body and then remove the
   * Basic Auth credentials from the request so that core authentication is
   * not performed later.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The input request.
   * @param int $type
   *   The type of the request. One of HttpKernelInterface::MASTER_REQUEST or
   *   HttpKernelInterface::SUB_REQUEST.
   * @param bool $catch
   *   Whether to catch exceptions or not.
   *
   * @throws \Exception
   *   When an Exception occurs during processing.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A Response instance
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if (
      strpos($request->getPathInfo(), '/oauth/token') !== FALSE &&
      $request->headers->has('PHP_AUTH_USER') &&
      $request->headers->has('PHP_AUTH_PW')
    ) {
      // Swap the Basic Auth credentials into the request data.
      $request->request->set('client_id', $request->headers->get('PHP_AUTH_USER'));
      $request->request->set('client_secret', $request->headers->get('PHP_AUTH_PW'));

      // Remove the Basic Auth credentials to prevent later authentication.
      $request->headers->remove('PHP_AUTH_USER');
      $request->headers->remove('PHP_AUTH_PW');
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }

}
