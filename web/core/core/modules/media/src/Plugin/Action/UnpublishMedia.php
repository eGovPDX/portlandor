<?php

namespace Drupal\media\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\media\MediaInterface;

/**
 * Unpublishes a media item.
 *
 * @Action(
 *   id = "media_unpublish_action",
 *   label = @Translation("Unpublish media"),
 *   type = "media"
 * )
 */
class UnpublishMedia extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(MediaInterface $entity = NULL) {
    if ($entity) {
      $entity->setUnpublished()->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\media\MediaInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
