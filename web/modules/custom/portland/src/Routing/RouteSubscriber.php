<?php

namespace Drupal\portland\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
    public static function getSubscribedEvents() {
        // Come after field_ui.
        $events[RoutingEvents::ALTER] = array(
          'onAlterRoutes',
          3100,
        );
        return $events;
      }

    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection) {
        // override route entity.group_content.group_node_add_page from Group module.
        // we want this to instead load a page that allows user to select a group content type,
        // but in our case, we want the content type descriptions to be displayed.
        if ($route = $collection->get('entity.group_content.group_node_add_page')) {
            $route->setDefault('_controller', '\Drupal\portland\Controller\PortlandController::addPage');
        }
        else if ($route = $collection->get('entity.group_content.group_media_add_page')) {
            $route->setDefault('_controller', '\Drupal\portland\Controller\PortlandMediaController::addPage');
        }
    }

}