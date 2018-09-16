<?php

namespace Drupal\portland\Routing;

use Drupal\search_api_page\Routing\SearchApiPageRoutes;

class PortlandSearchApiPageRoutes extends SearchApiPageRoutes {

  public function routes () {
    $routes = parent::routes();

    foreach ($routes as $route) {

        $route['_controller'] = 'Drupal\portland\Controller\PortlandSearchApiPageController::page';
        $route['_title_callback'] = 'Drupal\portland\Controller\PortlandSearchApiPageController::title';

    }
  }
}
