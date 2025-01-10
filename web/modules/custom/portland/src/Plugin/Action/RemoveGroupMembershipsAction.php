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
 * Views bulk operations action that allows removing users from multiple groups in bulk.
 *
 * @Action(
 *   id = "portland_remove_user_from_groups",
 *   label = @Translation("Remove members"),
 *   type = "group",
 *   confirm = TRUE,
 * )
 */
final class RemoveGroupMembershipsAction extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute(Group $group = NULL) {
    if ($group === NULL || \count($this->configuration['user_id']) === 0) {
      return $this->t('Invalid entity or configuration.');
    }

    $user_ids = [];
    foreach ($this->configuration['user_id'] as $item) {
      $user_ids[$item['target_id']] = $item['target_id'];
    }

    foreach (\Drupal::entityTypeManager()->getStorage('user')->loadMultiple($user_ids) as $user) {
      $membership = $group->getMember($user);
      if ($membership) {
        $group->removeMember($user);
      }
    }

    return $this->t('User has been removed from the group.');
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
