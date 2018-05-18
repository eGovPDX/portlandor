<?php

namespace Drupal\simple_oauth\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\simple_oauth\Authentication\Provider\SimpleOauthAuthenticationProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class DisallowSimpleOauthRequests implements RequestPolicyInterface {

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    return SimpleOauthAuthenticationProvider::hasTokenValue($request) ? self::DENY : NULL;
  }

}
