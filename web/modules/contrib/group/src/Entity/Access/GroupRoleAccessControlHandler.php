<?php

namespace Drupal\group\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the group role entity type.
 *
 * @see \Drupal\group\Entity\GroupRole
 */
class GroupRoleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\group\Entity\GroupRoleInterface $entity */
    if ($operation == 'delete') {
      return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
    }
    
    // Group roles have no 'view' route but may be used in views to show what
    // roles a member has. We therefore allow 'view' access so field formatters
    // such as entity_reference_label will work.
    if ($operation == 'view') {
      return AccessResult::allowed()->addCacheableDependency($entity);
    }
    
    return parent::checkAccess($entity, $operation, $account);
  }

}
