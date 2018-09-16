<?php

namespace Drupal\portland\Controller;

use Drupal\search_api_page\Controller\SearchApiPageController;
use Symfony\Component\HttpFoundation\Request;

 /**
  * Undocumented class
  */
class PortlandSearchApiPageController extends SearchApiPageController {

  public function page(Request $request, $search_api_page_name, $keys = '') {
    $render = parent::page($request, $search_api_page_name, $keys);

    $render['#form'] = [
      '#theme' => 'portland_search_form',
      '#size' => 'big',
      '#input' => $render['#form']['keys'],
      '#buttons' => $render['#form']['actions']['submit'],
    ];

    return $render;
  }
}
