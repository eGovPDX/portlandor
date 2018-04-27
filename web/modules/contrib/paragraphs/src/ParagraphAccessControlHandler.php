<?php

namespace Drupal\paragraphs;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the paragraphs entity.
 *
 * @see \Drupal\paragraphs\Entity\Paragraph.
 */
class ParagraphAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $paragraph, $operation, AccountInterface $account) {
    // Allowed when the operation is not view or the status is true.
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $access_result = AccessResult::allowedIf($operation != 'view' || $paragraph->status->value);
    if ($paragraph->getParentEntity() != NULL) {
      // Delete permission on the paragraph, should just depend on 'update'
      // access permissions on the parent.
      $operation = ($operation == 'delete') ? 'update' : $operation;
      // Library items have no support for parent entity access checking.
      if ($paragraph->getParentEntity()->getEntityTypeId() != 'paragraphs_library_item') {
        $parent_access = $paragraph->getParentEntity()->access($operation, $account, TRUE);
        $access_result = $access_result->andIf($parent_access);
      }
    }
    return $access_result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Allowed when nobody implements.
    return AccessResult::allowed();
  }

}
