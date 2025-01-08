<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;


/**
 * Remove group membership action
 *
 * @Action(
 *   id = "portland_remove_group_membership",
 *   label = @Translation("Remove users from group"),
 *   type = "group_content",
 *   confirm = TRUE,
 * )
 */
class RemoveGroupMembershipAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $pluginId = $entity->getPlugin()->getPluginId();
    
    if ($pluginId == "group_membership") {
      $entity->delete();
      return t('User has been removed from the group.');
    } else {
      return t('Selected entity is not a user.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // The "Items selected" list on group_content-based views has the user’s name. Include the 
    // selected groups' name too.
    $list = $form_state->getStorage()['views_bulk_operations']['list'];
    $count = 0;
    foreach ($list as $item) {
      $entity_id = $item[0];
      $username = $form['list']['#items'][$count];
      $group = \Drupal::entityTypeManager()->getStorage('group_content')->load($entity_id)->getGroup();
      $form['list']['#items'][$count++] = "$username in " . $group->label();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // If certain fields are updated, access should be checked against them as well.
    // @see Drupal\Core\Field\FieldUpdateActionBase::access().
    return $object->access('delete', $account, $return_as_object);
  }
}
