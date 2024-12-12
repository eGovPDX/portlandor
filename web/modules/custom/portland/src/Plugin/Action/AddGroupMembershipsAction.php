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
use Drupal\Core\Access\AccessResult;

/**
 * Views bulk operations action that allows adding users to multiple groups in bulk.
 *
 * @Action(
 *   id = "portland_add_user_to_groups",
 *   label = @Translation("Add or update members"),
 *   type = "group",
 *   confirm = FALSE,
 * )
 */
final class AddGroupMembershipsAction extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute(Group $group = NULL) {
    if ($group === NULL || \count($this->configuration['user_id']) === 0) {
      return $this->t('Invalid entity or configuarion.');
    }

    $user_ids = [];
    foreach ($this->configuration['user_id'] as $item) {
      $user_ids[$item['target_id']] = $item['target_id'];
    }

    $role_ids = $this->configuration['role_ids'];
    foreach (\Drupal::entityTypeManager()->getStorage('user')->loadMultiple($user_ids) as $user) {
      $membership = $group->getMember($user);
      if ($membership === FALSE) {
        // Add user to the group with the assigned role(s)
        $role_ids = array_diff($role_ids, [0]);   // Remove array elements with the value of "0"
        $group->addMember($user, ['group_roles' => array_keys($role_ids)]);
      } else {
        // User is already a member of the group so just update the role(s)
        foreach ($role_ids as $role_id => $assign_role) {
          if ($assign_role) {
            $membership->addRole($role_id);
          } else {
            $membership->removeRole($role_id);
          }
        }
      }
    }

    return $this->t('User has been added to the group with the assigned roles.');
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['user_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('User'),
      '#placeholder' => $this->t('Enter user name'),
      '#target_type' => 'user',
      '#selection_settings' => ['filter' => ['access' => 'administer members']],
      '#required' => TRUE,
      '#tags' => TRUE,
      '#description' => $this->t('Use a comma to select multiple users.'),
    ];
    
    // Get the group roles associated with the first selected group
    // HACK ALERT: This code assumes that all selected groups are of the same group type
    // and thereby have the same roles.
    $groups = $form_state->getStorage()['views_bulk_operations']['list'];
    $first_key = array_keys($groups)[0];
    $group_id = $groups[$first_key][0];
    $roles = \Drupal::entityTypeManager()->getStorage('group')->load($group_id)->getGroupType()->getRoles(FALSE);
    $role_options = array();
    foreach ($roles as $role) {
      $role_options[$role->id()] = $role->label();
    }
    $form['role_ids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $role_options,
      '#required' => TRUE,
      '#description' => $this->t('IMPORTANT: If a user already belongs to a selected group, this will remove their current roles and assign the roles selected here.'),
    ];
    
    return $form;
  }


    /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $has_access = $object->hasPermission('administer members', $account, $return_as_object);
    if ($return_as_object) {
      return $has_access ? AccessResult::allowed() : AccessResult::forbidden();
    } else {
      return $has_access;
    }
  }
}
