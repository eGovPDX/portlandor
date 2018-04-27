<?php

namespace Drupal\simple_oauth\Repositories;

use Drupal\user\UserAuthInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Drupal\simple_oauth\Entities\UserEntity;

class UserRepository implements UserRepositoryInterface {

  /**
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * UserRepository constructor.
   *
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The service to check the user authentication.
   */
  public function __construct(UserAuthInterface $user_auth) {
    $this->userAuth = $user_auth;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity) {
    // TODO: Use authenticateWithFloodProtection when #2825084 lands.
    if ($uid = $this->userAuth->authenticate($username, $password)) {
      $user = new UserEntity();
      $user->setIdentifier($uid);

      return $user;
    }

    return NULL;
  }

}
