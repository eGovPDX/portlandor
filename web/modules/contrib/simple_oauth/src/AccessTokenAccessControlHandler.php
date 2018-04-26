<?php

namespace Drupal\simple_oauth;

use Drupal\consumers\AccessControlHandler;

/**
 * Access controller for the Access Token entity.
 *
 * @see \Drupal\simple_oauth\Entity\AccessToken.
 */
class AccessTokenAccessControlHandler extends AccessControlHandler {

  public static $name = 'simple_oauth';

}
