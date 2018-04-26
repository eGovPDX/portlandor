<?php

namespace Drupal\simple_oauth\Entities;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface {

  use EntityTrait;

}
