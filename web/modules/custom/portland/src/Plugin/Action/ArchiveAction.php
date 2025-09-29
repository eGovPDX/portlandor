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
 *   id = "portland_archive_action",
 *   label = @Translation("Archive content (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class ArchiveAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  private function getModerationStateName($entity) {
    $entity_type = $entity->getEntityTypeId();
    if ($entity_type === 'node') {
      // The machine name of the unpublished state for some workflows is "archived", whereas most use "unpublished"
      // so we need to figure out the correct state name depending on the entity's associated workflow.

      // Get workflow associated with the entity bundle
      $bundle = $entity->bundle();
      $query = \Drupal::entityTypeManager()->getStorage('workflow')->getQuery();
      $query->condition('type_settings.entity_types.node.*', $bundle);
      $workflow = $query->execute();
      $workflow = array_shift($workflow);

      // Get the workflow's states and set the entity's moderation state to either
      // 'unpublished' or 'archived' depending on correct workflow state name.
      $workflow_states = \Drupal::config('workflows.workflow.' . $workflow)->get('type_settings.states');
      return array_key_exists('unpublished', $workflow_states) ? 'unpublished' : 'archived';
    } else if ($entity_type === 'media') {
      return 'unpublished_archived';
    } else {
      throw new \InvalidArgumentException("Unsupported entity type {$entity_type}");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $unpublished_moderation_state_name = $this->getModerationStateName($entity);
    if ($entity->status->value == 0 && $entity->moderation_state->value == $unpublished_moderation_state_name) {
      return $this->t("Is already unpublished.");
    }

    // Get current user's display name
    $user_display_name = \Drupal::currentUser()->getDisplayName();

    $entity->status->value = 0;
    $entity->moderation_state->value = $unpublished_moderation_state_name;

    // Make this change a new revision
    if ($entity->getEntityType()->isRevisionable()) {
      $entity->setNewRevision(TRUE);
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
      if ($entity instanceof RevisionLogInterface) {
        $entity->setRevisionLogMessage('Bulk operation: unpublished by '. $user_display_name);
      }
    }

    $entity->save();

    return $this->t('Bulk operation: unpublished by '. $user_display_name);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityTypeId() === 'node' || $object->getEntityTypeId() === 'media') {
      $unpublished_moderation_state_name = $this->getModerationStateName($object);
      // The update permission's name could be either Update or Edit, we check both and allow access if either one is allowed.
      $access_result_for_update = $object->access('update', $account);
      $access_result_for_edit = $object->access('edit', $account);
      $access = $access_result_for_update || $access_result_for_edit ? AccessResult::allowed() : AccessResult::forbidden();

      // For moderated content, edit access to the entity is not enough.
      // Users should also have permission to the proper workflow transition to archive moderated content.
      $moderation_info = \Drupal::service('content_moderation.moderation_information');
      if ($moderation_info->isModeratedEntity($object)) {
        // Find the workflow transition to archived state from the current moderation state and
        // get the workflow ID and transition ID so we can build the permission name to check.
        $workflow_id = $moderation_info->getWorkflowForEntity($object)->get('id');
        $transitions = $moderation_info->getWorkflowForEntity($object)->get('type_settings')['transitions'];
        $moderation_state = $object->get('moderation_state')->getString();
        $transition_id = null;
        foreach ($transitions as $key => $transition) {
          if ($transition['to'] == $unpublished_moderation_state_name && in_array($moderation_state, $transition['from'])) {
            $transition_id = $key;
            break;
          }
        }
        if ($transition_id == null) {
          // No transition to archived state found.
          // (There is no transition from archived->archived, so provide a reason in the access result if that's the case)
          return ($return_as_object
            ? AccessResult::forbidden()
              ->setReason($moderation_state == $unpublished_moderation_state_name ? 'Is already unpublished' : '')
            : FALSE);
        }

        // The workflow transition permission is named like "use editorial transition archive"
        $access = $access->andIf(AccessResult::allowedIfHasPermission($account, "use $workflow_id transition $transition_id"));
      }

      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different access methods and properties.
    return ($return_as_object ? AccessResult::allowed() : true );
  }
}
