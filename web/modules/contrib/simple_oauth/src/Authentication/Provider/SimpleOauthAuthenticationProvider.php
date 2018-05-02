<?php

namespace Drupal\simple_oauth\Authentication\Provider;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_oauth\Authentication\TokenAuthUser;
use Drupal\simple_oauth\Server\ResourceServerInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class SimpleOauthAuthenticationProvider implements SimpleOauthAuthenticationProviderInterface {

  /**
   * @var \Drupal\simple_oauth\Server\ResourceServerInterface
   */
  protected $resourceServer;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\simple_oauth\Server\ResourceServerInterface $resource_server
   *   The resource server object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ResourceServerInterface $resource_server, EntityTypeManagerInterface $entity_type_manager) {
    $this->resourceServer = $resource_server;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // Check for the presence of the token.
    return $this->hasTokenValue($request);
  }

  /**
   * {@inheritdoc}
   */
  public static function hasTokenValue(Request $request) {
    // Check the header. See: http://tools.ietf.org/html/rfc6750#section-2.1
    $auth_header = trim($request->headers->get('Authorization', '', TRUE));

    return strpos($auth_header, 'Bearer ') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // Update the request with the OAuth information.
    try {
      $request = $this->resourceServer->validateAuthenticatedRequest($request);
    }
    catch (OAuthServerException $exception) {
      // Procedural code here is hard to avoid.
      watchdog_exception('simple_oauth', $exception);

      return NULL;
    }

    $tokens = $this->entityTypeManager->getStorage('oauth2_token')->loadByProperties([
      'value' => $request->get('oauth_access_token_id'),
    ]);
    $token = reset($tokens);
    return new TokenAuthUser($token);
  }

}
