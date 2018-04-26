<?php

namespace Drupal\simple_oauth\Authentication;

use Drupal\user\UserInterface;

interface TokenAuthUserInterface extends \IteratorAggregate, UserInterface {

  /**
   * Get the token.
   *
   * @return \Drupal\simple_oauth\Entity\Oauth2TokenInterface
   *   The provided OAuth2 token.
   */
  public function getToken();

}
