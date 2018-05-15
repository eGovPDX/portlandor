<?php

namespace Drupal\portland\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

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
    }

}