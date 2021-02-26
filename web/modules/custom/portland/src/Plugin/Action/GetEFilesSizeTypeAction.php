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
 *   id = "portland_get_efiles_size_type_action",
 *   label = @Translation("Get eFile size and type (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class GetEFilesSizeTypeAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if( $entity->getEntityTypeId() !== 'media' || 
        ! $entity->hasField('field_efiles_link') || 
        $entity->field_efiles_link->count() == 0 ) {
      return $this->t('Is not eFiles document.');
    }

    $external_file_url = strtolower($entity->field_efiles_link[0]->uri);
    // Append "/file/document" if it's missing
    if( strpos($external_file_url, 'https://efiles.portlandoregon.gov/') === 0 &&
      ! (substr_compare($external_file_url, "/file/document", -strlen("/file/document") ) === 0) ) {
      $external_file_url .= "/file/document";
    }

    // Get eFiles file size and MIME type
    try {
      $headers = get_headers($external_file_url, 1);
    }catch (Exception $e) {
      return $this->t('Failed to retrieve external document');
    }

    if( !empty($headers) && $headers[0] === "HTTP/1.1 200 OK" && 
        array_key_exists('Content-Length', $headers) &&
        array_key_exists('Content-Type', $headers) ) {
      $file_size = (int)$headers['Content-Length'];
      if($file_size === 0) {
        return $this->t('External document is empty');
      }
      // Content-Type header can be "text/html; charset=utf-8"
      $content_type_array = explode(';', $headers['Content-Type']);
      // If content type is text/HTML, it's not a file
      if(empty($content_type_array) || $content_type_array[0] === "text/html") {
        return $this->t('The external document is a web page');
      }

      $entity->field_file_size->value = $file_size;
      $entity->field_mime_type->value = $content_type_array[0];
      $entity->save();
      return $this->t('Bulk operation: updated file size and mime type');
    }
    return $this->t('Failed to retrieve external document');
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
