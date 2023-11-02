<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;

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

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    if( $entity->status->value == 0 ) {
      if( ($entity->getEntityTypeId() == 'node' && in_array($entity->moderation_state->value, array('archived', 'unpublished')) ) ||
          ($entity->getEntityTypeId() == 'media' && $entity->moderation_state->value == 'unpublished_archived') ) {
        $title = $entity->title->value;
        return $this->t("'$title' is already unpublished.");
      }
    }

    // Get current user's display name
    $user_display_name = \Drupal::currentUser()->getDisplayName();

    $entity->status->value = 0;
    if($entity->getEntityTypeId() == 'node') {
      // The machine name of the unpublished state for some workflows is "archived", whereas most use "unpublished" 
      // so we need to figure out the correct state name depending on the entity's associated workflow.

      // Get workflow associated with the entity bundle
      $bundle = $entity->bundle();
      $query = \Drupal::entityTypeManager()->getStorage('workflow')->getQuery();
      $query->condition('type_settings.entity_types.node.*', $bundle);
      $workflow = $query->execute();
      $workflow = array_shift( $workflow );

      // Get the workflow's states and set the entity's moderation state to either
      // 'unpublished' or 'archived' depending on correct workflow state name.
      $workflow_states = \Drupal::config("workflows.workflow." . $workflow)->get("type_settings.states");
      $entity->moderation_state->value = array_key_exists('unpublished', $workflow_states) ? 'unpublished' : 'archived';
    }
    else if($entity->getEntityTypeId() == 'media') {
      $entity->moderation_state->value = 'unpublished_archived';
    }

    $entity->setNewRevision(TRUE);
    if($entity->hasField('revision_log'))
      $entity->revision_log = 'Bulk operation: unpublished by '. $user_display_name;
    $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
    $entity->setRevisionUserId(\Drupal::currentUser()->id());
    $entity->save();
    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Bulk operation: unpublished by '. $user_display_name);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityTypeId() === 'node' || $object->getEntityTypeId() === 'media') {
      // The update permission's name could be either Update or Edit, we check both and allow access if either one is allowed.
      // Please note the Core API AccessResult::orIf will return Forbidden if either access result is Forbidden
      $access_result_for_update = $object->access('update', $account);
      $access_result_for_edit = $object->access('edit', $account);
      if( $access_result_for_update || $access_result_for_edit ) {
        return ($return_as_object ? AccessResult::allowed() : true );
      }
      else {
        return ($return_as_object ? AccessResult::forbidden() : false );
      }
    }

    // Other entity types may have different
    // access methods and properties.
    return ($return_as_object ? AccessResult::allowed() : true );
  }
}
