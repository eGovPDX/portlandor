<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;

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

    if( $entity->status->value == 1)
      return $this->t('Is already published.');

    $entity->status->value = 1;
    $entity->moderation_state->value = 'published';
    // Make this change a new revision
    if($entity->hasField('revision_log'))
      $entity->revision_log = 'Bulk operation: published by '. $user_display_name;
    $entity->setNewRevision(TRUE);
    $entity->setRevisionCreationTime(REQUEST_TIME);
    $entity->setRevisionUserId(\Drupal::currentUser()->id());
    $entity->save();

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Bulk operation: published by '. $user_display_name);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityType() === 'node' || $object->getEntityType() === 'media') {
      $access = $object->access('update', $account, TRUE)
        ->andIf($object->status->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }
}
