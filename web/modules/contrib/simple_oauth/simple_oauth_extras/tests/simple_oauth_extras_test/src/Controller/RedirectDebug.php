<?php

namespace Drupal\simple_oauth_extras_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RedirectDebug extends ControllerBase {

  /**
   * Debug the token response for the implicit grant.
   */
  public function token(Request $request) {
    return new JsonResponse($request->getRequestUri());
  }

}
