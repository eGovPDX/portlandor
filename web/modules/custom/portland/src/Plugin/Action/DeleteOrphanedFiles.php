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
class DeleteOrphanedFiles extends ViewsBulkOperationsActionBase
{

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL)
  {
    if ($entity == null || $entity->bundle() != 'document')
      return $this->t("Not applicable");
    if (!$entity->isLatestRevision()) {
      return $this->t("Revision processed");
    }
    $media_storage = \Drupal::entityTypeManager()->getStorage('media');
    $latest_revision_id = $media_storage->getLatestRevisionId($entity->id());
    $latest_file_id = $media_storage->loadRevision($latest_revision_id)->field_document->target_id;

    // Load all revisions and their file IDs
    $conn = \Drupal\Core\Database\Database::getConnection();
    $query = $conn->select('media_revision__field_document', 'media_revision__field_document');
    $query->condition('media_revision__field_document.entity_id', $entity->id(), '=');
    $query->fields('media_revision__field_document', ['revision_id', 'field_document_target_id']);
    $result = $query->execute();

    // Delete all but the latest revision
    foreach ($result as $row) {
      if($row->revision_id == $latest_revision_id) continue;
      // Delete the associated file if it's not used in the latest revision
      if($row->field_document_target_id != $latest_file_id) {
        file_delete($row->field_document_target_id);
      }
      $media_storage->deleteRevision($row->revision_id);
    }
    return $this->t("Document processed");
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    if ($account == null) return false;
    return $account->hasPermission('administer media');
  }
}
