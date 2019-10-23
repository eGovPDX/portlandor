<?php

namespace Drupal\Tests\media_embed_field\Functional;

use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Create admin users.
 */
trait AdminUserTrait {

  use UserCreationTrait;

  /**
   * Create an admin user.
   *
   * @return \Drupal\user\UserInterface
   *   A user with all permissions.
   */
  protected function createAdminUser() {
    return $this->drupalCreateUser(array_keys($this->container->get('user.permissions')->getPermissions()));
  }

}
