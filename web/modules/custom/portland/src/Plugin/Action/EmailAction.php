<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Email action
 *
 * @Action(
 *   id = "portland_email",
 *   label = @Translation("Email action"),
 *   type = "",
 *   confirm = FALSE,
 *   requirements = {
 *     "_permission" = "publisher",
 *     "_custom_access" = FALSE,
 *   },
 * )
 */
class EmailAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $uid = $entity->get('entity_id')->__get('target_id');
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    $user_email = $user->__get('mail')->__get('value');

    return "$user_email; ";
  }


  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $results = [];
    foreach ($objects as $entity) {
      $results[] = $this->execute($entity);
    }

    $combined_result = "";
    foreach ($results as $result) {
      $combined_result .= $result;
    }
    return ["Copy these email addresses into Outlook's 'To:' field: $combined_result"];
  }


  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // If certain fields are updated, access should be checked against them as well.
    // @see Drupal\Core\Field\FieldUpdateActionBase::access().
    return $object->access('update', $account, $return_as_object);
  }

}
