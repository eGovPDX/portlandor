<?php

namespace Drupal\simple_oauth\Plugin;

interface Oauth2GrantManagerInterface {

  /**
   * Gets the authorization server.
   *
   * @param string $grant_type
   *   The grant type used as plugin ID.
   *
   * @throws \League\OAuth2\Server\Exception\OAuthServerException
   *   When the grant cannot be found.
   *
   * @return \League\OAuth2\Server\AuthorizationServer
   *   The authorization server.
   */
  public function getAuthorizationServer($grant_type);

}
