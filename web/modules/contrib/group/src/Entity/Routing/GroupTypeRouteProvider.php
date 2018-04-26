<?php

namespace Drupal\group\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Provides routes for group types.
 */
class GroupTypeRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    // @todo Remove this method when https://www.drupal.org/node/2767025 lands.
    if ($route = parent::getCollectionRoute($entity_type)) {
      $route->setDefault('_title', 'Group types');
      $route->setDefault('_title_arguments', []);
      return $route;
    }
  }

}
