<?php

namespace Drupal\simple_oauth\Server;

use Drupal\Core\Config\ConfigFactoryInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\ResourceServer as LeageResourceServer;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class ResourceServer implements ResourceServerInterface {

  /**
   * @var \League\OAuth2\Server\ResourceServer
   */
  protected $subject;

  /**
   * @var \Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface
   */
  protected $messageFactory;

  /**
   * @var \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface
   */
  protected $foundationFactory;

  /**
   * ResourceServer constructor.
   */
  public function __construct(
    AccessTokenRepositoryInterface $access_token_repository,
    ConfigFactoryInterface $config_factory,
    HttpMessageFactoryInterface $message_factory,
    HttpFoundationFactoryInterface $foundation_factory
  ) {
    try {
      $public_key = $config_factory->get('simple_oauth.settings')->get('public_key');
      $public_key_real = realpath($public_key);
      if ($public_key && $public_key_real) {
        $this->subject = new LeageResourceServer(
          $access_token_repository,
          $public_key_real
        );
      }
    }
    catch (\LogicException $exception) {
    }
    $this->messageFactory = $message_factory;
    $this->foundationFactory = $foundation_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function validateAuthenticatedRequest(Request $request) {
    // Create a PSR-7 message from the request that is compatible with the OAuth
    // library.
    $psr7_request = $this->messageFactory->createRequest($request);
    // Augment the request with the access token's decoded data or throw an
    // exception if authentication is unsuccessful.
    $output_psr7_request = $this
      ->subject
      ->validateAuthenticatedRequest($psr7_request);

    // Convert back to the Drupal/Symfony HttpFoundation objects.
    return $this->foundationFactory->createRequest($output_psr7_request);
  }

}
