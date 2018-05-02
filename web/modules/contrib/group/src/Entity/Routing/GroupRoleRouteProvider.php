<?php

namespace Drupal\group\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Provides routes for group roles.
 */
class GroupRoleRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddFormRoute($entity_type)) {
      $route->setOption('parameters', ['group_type' => ['type' => 'entity:group_type']]);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getEditFormRoute($entity_type)) {
      // @todo Remove title part when https://www.drupal.org/node/2827739 lands.
      $route->setDefault('_title_callback', '\Drupal\group\Entity\Controller\GroupRoleController::editTitle');
      $route->setOption('parameters', ['group_type' => ['type' => 'entity:group_type']]);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getDeleteFormRoute($entity_type)) {
      // @todo Remove title part when https://www.drupal.org/node/2827739 lands.
      $route->setDefault('_title_callback', '\Drupal\group\Entity\Controller\GroupRoleController::deleteTitle');
      $route->setOption('parameters', ['group_type' => ['type' => 'entity:group_type']]);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCollectionRoute($entity_type)) {
      // @todo Remove title part when https://www.drupal.org/node/2767025 lands.
      $route->setDefault('_title', 'Group roles');
      $route->setDefault('_title_arguments', []);
      $route->setOption('parameters', ['group_type' => ['type' => 'entity:group_type']]);
      return $route;
    }
  }

}
