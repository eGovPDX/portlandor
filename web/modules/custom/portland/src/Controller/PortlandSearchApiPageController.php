<?php

namespace Drupal\portland\Controller;

use Drupal\search_api_page\Controller\SearchApiPageController;
use Symfony\Component\HttpFoundation\Request;

 /**
  * Override SearchApiPageController form display
  */
class PortlandSearchApiPageController extends SearchApiPageController {

  public function page(Request $request, $search_api_page_name, $keys = '') {
    // Get the form from base SearchApiPageController
    $render = parent::page($request, $search_api_page_name, $keys);

    // Add a child to the form that contains our template for a search input
    $render['#form']['box'] = [
      '#theme' => 'portland_search_form',
      '#size' => 'big',
      '#input' => $render['#form']['keys'],
      '#buttons' => $render['#form']['actions']['submit'],
    ];

    // remove search input and submit button from original form
    unset($render['#form']['keys'], $render['#form']['actions']);

    return $render;
  }
}
