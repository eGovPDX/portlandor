<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Access\AccessResult;

/**
 * Views bulk operations action that allows adding users to multiple groups in bulk.
 *
 * @Action(
 *   id = "portland_add_user_to_groups_relationship",
 *   label = @Translation("Add or update members"),
 *   type = "group_content",
 *   confirm = FALSE,
 * )
 */
class AddGroupRelationshipMembershipsAction extends AddGroupMembershipsAction implements PluginFormInterface {
  // This class is just a copy of AddGroupMembershipsAction that is applied to group content entities.

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    // The "Items selected" list on group_content-based views has the user’s name instead of the 
    // selected group(s). Change them to the group's name instead.
    $list = $form_state->getStorage()['views_bulk_operations']['list'];
    $count = 0;
    foreach ($list as $item) {
      $entity_id = $item[0];
      $group = \Drupal::entityTypeManager()->getStorage('group_content')->load($entity_id)->getGroup();
      $form['list']['#items'][$count++] = $group->label();
    }

    return $form;
  }
}
