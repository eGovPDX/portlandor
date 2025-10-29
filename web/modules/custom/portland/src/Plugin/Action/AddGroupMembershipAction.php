<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\user\UserInterface;
use Drupal\group\Entity\GroupInterface;


/**
 * Views bulk operations action that allows adding mulitple users to a group in bulk.
 *
 * @Action(
 *   id = "portland_add_users_to_group",
 *   label = @Translation("Add users to a group"),
 *   type = "user",
 *   confirm = FALSE,
 * )
 */
final class AddGroupMembershipAction extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute(?UserInterface $user = NULL) {
    if ($user === NULL || $this->configuration['group_id'] === 0) {
      return $this->t('Invalid entity or configuration.');
    }

    $group_id = $this->configuration['group_id'];
    $role_ids = $this->configuration['role_ids'];
    /** @var GroupInterface $group */
    $group = \Drupal::entityTypeManager()->getStorage('group')->load($group_id);
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

    return $this->t('User has been added to the group with the assigned roles.');
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Step tracking
    $current_step = $form_state->get('current_step') ?? 1;
    
    // Build the form based on the current step
    switch ($current_step) {
      case 1:
        $form['step_desc'] = [
          '#type' => 'markup',
          '#markup' => '<h4>' . t('Step 1 of 2: Select the group') . '</h4>',
        ];
        $form['group_id'] = [
          '#type' => 'entity_autocomplete',
          '#title' => $this->t('Group'),
          '#placeholder' => $this->t('Enter group name'),
          '#target_type' => 'group',
          '#selection_settings' => ['filter' => ['access' => 'administer members']],
          '#required' => TRUE,
          '#tags' => FALSE,
        ];
        
        $form['actions']['submit']['#value'] = t('Next');
        break;
      
      case 2:
        $form['step_desc'] = [
          '#type' => 'markup',
          '#markup' => '<h4>' . t('Step 2 of 2: Assign group role(s)') . '</h4>',
        ];
        
        $form['group'] = [
          '#type' => 'markup',
          '#markup' => '<p><strong>Group:</strong> ' . $form_state->getUserInput()['group_id'] . '</p>',
        ];
        
        $group_id = $form_state->getValue('group_id');
        $form['group_id'] = [
          '#type' => 'hidden',
          '#value' => $group_id,
        ];

        // Get the group roles associated with the selected group
        /** @var GroupInterface $group */
        $group = \Drupal::entityTypeManager()->getStorage('group')->load($group_id);
        $roles = $group->getGroupType()->getRoles(FALSE);
        $role_options = array();
        foreach ($roles as $role) {
          $role_options[$role->id()] = $role->label();
        }
        $form['role_ids'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Roles'),
          '#options' => $role_options,
          '#required' => TRUE,
          '#description' => $this->t('IMPORTANT: If a user already belongs to the selected group, this will remove their current roles and assign the roles selected here.'),
        ];
        break;
    }

    // Store the current step
    $form_state->set('current_step', $current_step);

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $button = $form_state->getTriggeringElement()['#value']->getUntranslatedString();

    if ($button === 'Next') {
      $current_step = $form_state->get('current_step');
      $form_state->set('current_step', ++$current_step);
      $form_state->setRebuild();
    }
  }


  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    // This checks for edit access on the selected users' accounts. We can't really check if the operator 
    // has the necessary group permissions because the group hasn't been chosen yet, but since this action is
    // only available on the /admin/people view which requires sitewide admin permissions, we can assume the
    // operator has the necessary permissions.
    return $object->access('edit', $account, $return_as_object);
  }

}
