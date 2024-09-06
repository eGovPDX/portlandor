<?php

namespace Drupal\portland_relations;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\portland_relations\Entity\RelationInterface;

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
    assert($entity instanceof RelationInterface);
    $bundle = $entity->bundle();

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view relations'),
      'update' => AccessResult::allowedIfHasPermission($account, "edit any $bundle relations"),
      'delete' => AccessResult::allowedIfHasPermission($account, "delete any $bundle relations"),
      'view revision', 'view all revisions' => AccessResult::allowedIfHasPermission($account, "view any $bundle relation revisions"),
      'revert' => AccessResult::allowedIfHasPermission($account, "revert any $bundle relation revisions"),
      'delete revision' => AccessResult::allowedIfHasPermission($account, "delete any $bundle relation revisions"),
      default => throw new \LogicException('Unknown operation'),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, "create $entity_bundle relations");
  }

}
