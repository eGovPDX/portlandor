<?php

namespace Drupal\simple_oauth\Repositories;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface {

  use RevocableTokenRepositoryTrait;

  protected static $bundle_id = 'refresh_token';
  protected static $entity_class = 'Drupal\simple_oauth\Entities\RefreshTokenEntity';
  protected static $entity_interface = 'League\OAuth2\Server\Entities\RefreshTokenEntityInterface';

  /**
   * {@inheritdoc}
   */
  public function getNewRefreshToken() {
    return $this->getNew();
  }

  /**
   * {@inheritdoc}
   */
  public function persistNewRefreshToken(RefreshTokenEntityInterface $refresh_token_entity) {
    $this->persistNew($refresh_token_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function revokeRefreshToken($token_id) {
    $this->revoke($token_id);
  }

  /**
   * {@inheritdoc}
   */
  public function isRefreshTokenRevoked($token_id) {
    return $this->isRevoked($token_id);
  }

}
