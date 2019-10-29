<?php

namespace Drupal\portland_relations;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Relation entity.
 *
 * @see \Drupal\portland_relations\Entity\Relation.
 */
class RelationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\portland_relations\Entity\RelationInterface $entity */

    switch ($operation) {

      case 'view':

        return AccessResult::allowedIfHasPermission($account, 'view relation entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit relation entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete relation entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add relation entities');
  }

}
