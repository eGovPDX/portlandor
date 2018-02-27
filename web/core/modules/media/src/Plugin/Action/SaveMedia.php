<?php

namespace Drupal\media\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\MediaInterface;

/**
 * Saves a media item.
 *
 * @Action(
 *   id = "media_save_action",
 *   label = @Translation("Save media"),
 *   type = "media"
 * )
 */
class SaveMedia extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(MediaInterface $entity = NULL) {
    if ($entity) {
      // We need to change at least one value, otherwise the changed timestamp
      // will not be updated.
      $entity->setChangedTime(0)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\media\MediaInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

}
