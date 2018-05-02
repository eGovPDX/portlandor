<?php

namespace Drupal\simple_oauth_extras\Plugin\Oauth2Grant;

use Drupal\simple_oauth\Plugin\Oauth2GrantBase;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;

/**
 * @Oauth2Grant(
 *   id = "client_credentials",
 *   label = @Translation("Client Credentials")
 * )
 */
class ClientCredentials extends Oauth2GrantBase {

  /**
   * {@inheritdoc}
   */
  public function getGrantType() {
    return new ClientCredentialsGrant();
  }

}
