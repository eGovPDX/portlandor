<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Some description.
 *
 * @Action(
 *   id = "portland_publish_action",
 *   label = @Translation("Publish content (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class PublishAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Get current user's display name
    $user_display_name = \Drupal::currentUser()->getDisplayName();

    if ($entity->status->value == 1) {
      return $this->t('Is already published.');
    }

    $entity->status->value = 1;
    $entity->moderation_state->value = 'published';
    // Make this change a new revision
    if ($entity->getEntityType()->isRevisionable()) {
      $entity->setNewRevision(TRUE);
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
      if ($entity instanceof RevisionLogInterface) {
        $entity->setRevisionLogMessage('Bulk operation: published by '. $user_display_name);
      }
    }

    $entity->save();

    return $this->t('Bulk operation: published by '. $user_display_name);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityTypeId() === 'node' || $object->getEntityTypeId() === 'media') {
      // The update permission's name could be either Update or Edit, we check both and allow access if either one is allowed.
      $access_result_for_update = $object->access('update', $account);
      $access_result_for_edit = $object->access('edit', $account);
      $access = $access_result_for_update || $access_result_for_edit ? AccessResult::allowed() : AccessResult::forbidden();

      // For moderated content, edit access to the entity is not enough.
      // Users should also have permission to the proper workflow transition to publish moderated content.
      $moderation_info = \Drupal::service('content_moderation.moderation_information');
      if ($moderation_info->isModeratedEntity($object)) {
        // Find the workflow transition to published state from the current moderation state and
        // get the workflow ID and transition ID so we can build the permission name to check.
        $workflow_id = $moderation_info->getWorkflowForEntity($object)->get('id');
        $transitions = $moderation_info->getWorkflowForEntity($object)->get('type_settings')['transitions'];
        $moderation_state = $object->get('moderation_state')->getString();
        $transition_id = null;
        foreach ($transitions as $key => $transition) {
          if ($transition['to'] == 'published' and in_array($moderation_state, $transition['from'])) {
            $transition_id = $key;
            break;
          }
        }
        if ($transition_id == null) {
          // No transition to published state found.
          return ($return_as_object ? AccessResult::forbidden() : FALSE);
        }

        // The workflow transition permission is named like "use editorial transition publish"
        $access = $access->andIf(AccessResult::allowedIfHasPermission($account, "use $workflow_id transition $transition_id"));
      }

      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different access methods and properties.
    return ($return_as_object ? AccessResult::allowed() : true );
  }
}
