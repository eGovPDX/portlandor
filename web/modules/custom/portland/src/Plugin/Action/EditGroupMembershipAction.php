<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\group\Entity\GroupMembership;
use Drupal\Core\Access\AccessResult;

/**
 * Views bulk operations action that allows editing multiple users group memberships in bulk.
 *
 * @Action(
 *   id = "portland_edit_users_membership",
 *   label = @Translation("Edit group roles"),
 *   type = "group_content",
 *   confirm = FALSE,
 * )
 */
final class EditGroupMembershipAction extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute(GroupMembership $membership = NULL) {
    if ($membership === NULL || \count($this->configuration['role_ids']) === 0) {
      return $this->t('Invalid entity or configuarion.');
    }

    $role_ids = $this->configuration['role_ids'];
    foreach ($role_ids as $role_id => $assign_role) {
      if ($assign_role) {
        $membership->addRole($role_id);
      } else {
        $membership->removeRole($role_id);
      }
    }

    return $this->t('The user\'s group roles have been updated.');
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get the group roles associated with the group
    $group_contents = $form_state->getStorage()['views_bulk_operations']['list'];
    $first_key = array_keys($group_contents)[0];
    $group_content_id = $group_contents[$first_key][0];
    $roles = \Drupal::entityTypeManager()->getStorage('group_content')->load($group_content_id)->getGroup()->getGroupType()->getRoles(FALSE);
    $role_options = array();
    foreach ($roles as $role) {
      $role_options[$role->id()] = $role->label();
    }
    $form['role_ids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $role_options,
      '#required' => TRUE,
      '#description' => $this->t('IMPORTANT: This will remove the users\' current roles and assign the roles selected here.'),
    ];
    
    return $form;
  }


    /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $has_access = $object->getGroup()->hasPermission('administer members', $account, $return_as_object);
    if ($return_as_object) {
      return $has_access ? AccessResult::allowed() : AccessResult::forbidden();
    } else {
      return $has_access;
    }
  }
}
