<?php

namespace Drupal\group\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Provides routes for groups.
 */
class GroupRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddPageRoute($entity_type)) {
      $route->setOption('_group_operation_route', TRUE);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddFormRoute($entity_type)) {
      $route->setOption('_group_operation_route', TRUE);
      $route->setDefault('_controller', '\Drupal\group\Entity\Controller\GroupController::addForm');
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getEditFormRoute($entity_type)) {
      $route->setOption('_group_operation_route', TRUE);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getDeleteFormRoute($entity_type)) {
      $route->setOption('_group_operation_route', TRUE);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    // @todo Remove this method when https://www.drupal.org/node/2767025 lands.
    if ($route = parent::getCollectionRoute($entity_type)) {
      $route->setDefault('_title', 'Groups');
      $route->setDefault('_title_arguments', []);
      return $route;
    }
  }

}
