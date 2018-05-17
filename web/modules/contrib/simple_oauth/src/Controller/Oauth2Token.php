<?php

namespace Drupal\simple_oauth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\simple_oauth\Plugin\Oauth2GrantManagerInterface;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Oauth2Token extends ControllerBase {

  /**
   * @var \Drupal\simple_oauth\Plugin\Oauth2GrantManagerInterface
   */
  protected $grantManager;

  /**
   * Oauth2Token constructor.
   *
   * @param \Drupal\simple_oauth\Plugin\Oauth2GrantManagerInterface $grant_manager
   */
  public function __construct(Oauth2GrantManagerInterface $grant_manager) {
    $this->grantManager = $grant_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.oauth2_grant.processor')
    );
  }

  /**
   * Processes POST requests to /oauth/token.
   */
  public function token(ServerRequestInterface $request) {
    // Extract the grant type from the request body.
    $body = $request->getParsedBody();
    $grant_type_id = !empty($body['grant_type']) ? $body['grant_type'] : 'implicit';
    // Get the auth server object from that uses the League library.
    try {
      // Respond to the incoming request and fill in the response.
      $auth_server = $this->grantManager->getAuthorizationServer($grant_type_id);
      $response = $this->handleToken($request, $auth_server);
    }
    catch (OAuthServerException $exception) {
      watchdog_exception('simple_oauth', $exception);
      $response = $exception->generateHttpResponse(new Response());
    }
    return $response;
  }

  /**
   * Handles the token processing.
   *
   * @param \Psr\Http\Message\ServerRequestInterface $psr7_request
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  protected function handleToken(ServerRequestInterface $psr7_request, AuthorizationServer $auth_server) {
    // Instantiate a new PSR-7 response object so the library can fill it.
    return $auth_server->respondToAccessTokenRequest($psr7_request, new Response());
  }

}
