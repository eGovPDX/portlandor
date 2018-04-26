<?php

namespace Drupal\crop;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the storage handler class for image crop storage.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class,
 * adding required special handling for comment entities.
 */
class CropStorage extends SqlContentEntityStorage implements CropStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getCrop($uri, $type) {
    $query = $this->database->select('crop_field_data', 'cfd');
    $query->addField('cfd', 'cid');
    $query->condition('cfd.uri', $uri, 'LIKE');

    if ($type) {
      $query->condition('cfd.type', $type);
    }

    $query->range(0, 1);

    $cid = $query->execute()->fetchField();
    return $cid ? $this->load($cid) : NULL;
  }

}
