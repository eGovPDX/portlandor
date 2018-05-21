<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Returns responses for GroupRole routes.
 */
class GroupRoleController extends EntityController  {

  /**
   * {@inheritdoc}
   */
  protected function doGetEntity(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($_entity) {
      $entity = $_entity;
    }
    // The parent function will only grab the first entity from the route. In
    // this case, that would incorrectly be the group type. We need to hard-code
    // the group_role parameter until https://www.drupal.org/node/2827739 lands.
    // @todo Keep an eye on https://www.drupal.org/node/2827739.
    elseif ($route_match->getRawParameter('group_role') !== NULL) {
      $entity = $route_match->getParameter('group_role');
    }
    if (isset($entity)) {
      return $this->entityRepository->getTranslationFromContext($entity);
    }
  }

}
