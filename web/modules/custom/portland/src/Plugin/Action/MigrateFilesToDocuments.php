<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Access\AccessResult;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Some description.
 *
 * @Action(
 *   id = "portland_migrate_files_to_documents",
 *   label = @Translation("Migrate files to documents (custom action)"),
 *   type = "node",
 *   confirm = FALSE,
 * )
 */
class MigrateFilesToDocuments extends ViewsBulkOperationsActionBase
{

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL)
  {
    $entity_types_to_migrate = ['change_set', 'council_document', 'city_code', 'policy'];

    if ($entity == null || 
    ! in_array($entity->bundle(), $entity_types_to_migrate) || 
    ! $entity->hasField('field_documents')) {
      return $this->t("Not applicable");
    }

    $original_documents_field_name = ( $entity->bundle() === 'change_set' ) ? 
      'field_change_documents' : 'field_documents_and_exhibits';

    // Check if the original documents field is empty
    if( $entity->bundle() === 'change_set' ) {
      if($entity->get($original_documents_field_name)->count() === 0 ) return $this->t("Not applicable");
    }
    else if($entity->get($original_documents_field_name)->count() === 0) {
      return $this->t("Not applicable");
    }

    // Loop through all files
    foreach($entity->get($original_documents_field_name) as $fileItem) {
      $file = File::load($fileItem->target_id);
      if(empty($file)) continue;

      // Create a new Document entity based on the file
      $media = Media::create([
        'bundle' => 'document',
        'uid' => 1,
        'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
        'name' => (empty($fileItem->description)) ? $file->getFilename() : $fileItem->description,
        'status' => 1,
        'field_document' => [
          'target_id' => $fileItem->target_id,
          'alt' => $file->getFilename(),
        ],
        'field_mime_type' => $file->getMimeType(),
        'field_file_size' => $file->getSize(),
        'field_display_groups' => "141", // charter-code-policies
      ]);
      $media->save();
      $media->status->value = 1;
      $media->moderation_state->value = 'published';
      $media->save();

      // Empty the file field
      $entity->set($original_documents_field_name, []);
      // Add the Document to the field_documents list
      $entity->field_documents []= $media->id();
      $entity->save();
    }
    return $this->t("File migration processed");
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    if ($account == null) return false;
    return AccessResult::allowed();
  }
}
