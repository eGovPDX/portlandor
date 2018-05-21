<?php

namespace Drupal\simple_oauth\Repositories;

use Drupal\simple_oauth\Entities\AccessTokenEntity;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface {

  use RevocableTokenRepositoryTrait;

  protected static $bundle_id = 'access_token';
  protected static $entity_class = 'Drupal\simple_oauth\Entities\AccessTokenEntity';
  protected static $entity_interface = 'League\OAuth2\Server\Entities\AccessTokenEntityInterface';

  /**
   * {@inheritdoc}
   */
  public function persistNewAccessToken(AccessTokenEntityInterface $access_token_entity) {
    $this->persistNew($access_token_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function revokeAccessToken($token_id) {
    $this->revoke($token_id);
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessTokenRevoked($token_id) {
    return $this->isRevoked($token_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getNewToken(ClientEntityInterface $client_entity, array $scopes, $user_identifier = NULL) {
    $access_token = new AccessTokenEntity();
    $access_token->setClient($client_entity);
    foreach ($scopes as $scope) {
      $access_token->addScope($scope);
    }
    $access_token->setUserIdentifier($user_identifier);

    return $access_token;
  }

}
