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
 *   type = "node",
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

    $entity->status->value = 1;
    $entity->moderation_state->value = '';
    // Make this change a new revision
    $entity->revision_log->value = 'Published by '. $user_display_name;
    $entity->setNewRevision(TRUE);
    $entity->setRevisionCreationTime(REQUEST_TIME);
    $entity->save();

    $entity->setPublished()->save();
    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Published by '. $user_display_name);
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