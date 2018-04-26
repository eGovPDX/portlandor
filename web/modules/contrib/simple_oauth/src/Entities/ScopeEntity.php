<?php

namespace Drupal\simple_oauth\Entities;

use Drupal\user\RoleInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ScopeEntity implements ScopeEntityInterface {

  use EntityTrait;

  /**
   * Construct a ScopeEntity instance.
   *
   * @param \Drupal\User\RoleInterface $role
   *   The role associated to the scope.
   */
  public function __construct(RoleInterface $role) {
    $this->role = $role;
    $this->setIdentifier($role->id());
  }

  public function jsonSerialize() {
    return $this->getIdentifier();
  }

}
