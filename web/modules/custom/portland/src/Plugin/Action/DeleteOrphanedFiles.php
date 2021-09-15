<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Some description.
 *
 * @Action(
 *   id = "portland_delete_orphaned_files",
 *   label = @Translation("Delete orphaned files (custom action)"),
 *   type = "media",
 *   confirm = TRUE,
 * )
 */
class DeleteOrphanedFiles extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if( $entity == null || $entity->bundle() != 'document') 
      return $this->t("Not applicable");

    // Don't process the latest revision
    if( ! $entity->isLatestRevision() ) {
      $media_storage = \Drupal::entityTypeManager()->getStorage('media');
      $latest_revision = $media_storage->loadRevision($media_storage->getLatestRevisionId($entity->id()));
      $latest_file_id = $latest_revision->field_document->target_id;
      $current_file_id = $entity->field_document->target_id;
      if($current_file_id != $latest_file_id) {
        if( $entity->field_document[0]->entity ) {
          $uri = $entity->field_document[0]->entity->getFileUri();
          $entity->field_document[0]->entity->delete();
        }
        else {
          $uri = 'file already deleted';
        }
        
        file_delete($current_file_id);

        // Don't return anything for a default completion message, otherwise return translatable markup.
        return $this->t("Deleted unused file: $uri");
      }
    }
    return $this->t("Skipped processing");
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if($account == null) return false;
    return $account->hasPermission('administer media');
  }
}
