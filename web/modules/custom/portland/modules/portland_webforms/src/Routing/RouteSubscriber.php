<?php

namespace Drupal\portland_webforms\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to route alterations to override Entity Usage controllers.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Override the main usage list route.
    if ($route = $collection->get('entity_usage.usage_list')) {
      $route->setDefault('_controller', 'portland_webforms.safe_list_usage_controller:listUsagePage');
    }
    
    // Override all dynamic entity local task routes (entity.{entity_type}.entity_usage).
    foreach ($collection->all() as $route_name => $route) {
      if (str_starts_with($route_name, 'entity.') && str_ends_with($route_name, '.entity_usage')) {
        $route->setDefault('_controller', 'portland_webforms.safe_local_task_usage_controller:listUsageLocalTask');
        $route->setDefault('_title_callback', 'portland_webforms.safe_local_task_usage_controller:getTitleLocalTask');
        $route->setRequirement('_custom_access', 'portland_webforms.safe_local_task_usage_controller:checkAccessLocalTask');
      }
    }
  }

}
