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
 *   id = "portland_archive_action",
 *   label = @Translation("Archive content (custom action)"),
 *   type = "node",
 *   confirm = FALSE,
 * )
 */
class ArchiveAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Get current user's display name
    $user_display_name = \Drupal::currentUser()->getDisplayName();

    $entity->status->value = 0;
    $entity->moderation_state->value = 'archived';
    $entity->setNewRevision(TRUE);
    $entity->revision_log = 'Archived by '. $user_display_name;
    $entity->setRevisionCreationTime(REQUEST_TIME);
    // $node->setRevisionUserId(\Drupal::currentUser()->id());
    $entity->save();
    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Archived by '. $user_display_name);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityType() === 'node') {
      $access = $object->access('update', $account, TRUE)
        ->andIf($object->status->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }
}