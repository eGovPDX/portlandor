<?php

namespace Drupal\simple_oauth_extras\Repositories;

use Drupal\simple_oauth\Repositories\RevocableTokenRepositoryTrait;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface {

  use RevocableTokenRepositoryTrait;

  protected static $bundle_id = 'auth_code';
  protected static $entity_class = 'Drupal\simple_oauth_extras\Entities\AuthCodeEntity';
  protected static $entity_interface = 'League\OAuth2\Server\Entities\AuthCodeEntityInterface';

  /**
   * {@inheritdoc}
   */
  public function getNewAuthCode() {
    return $this->getNew();
  }

  /**
   * {@inheritdoc}
   */
  public function persistNewAuthCode(AuthCodeEntityInterface $auth_code_entity) {
    $this->persistNew($auth_code_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function revokeAuthCode($code_id) {
    $this->revoke($code_id);
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthCodeRevoked($code_id) {
    return $this->isRevoked($code_id);
  }

}
