<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;

/**
 * Some description.
 *
 * @Action(
 *   id = "change_group_action",
 *   label = @Translation("Change parent group of Content (custom action)"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class ChangeGroupAction extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Remove from all groups.
    $group_contents = \Drupal::entityTypeManager()
        ->getStorage('group_content')
        ->loadByProperties([ 
            'entity_id' => $entity->id(),
            // 'gid' => $old_group_id, // The item can only belong to one group. Don't need group id.
    ]);
    foreach ($group_contents as $item) {
        $item->delete();
    }

    if($this->configuration['new_group'] == 0) {
      // Update the Group field value
      $entity->field_group = NULL;
      $entity->save();
    }
    else {
      // Add to new group
      $group = Group::load($this->configuration['new_group']);
      $group->addContent($entity, 'group_'.$entity->getEntityTypeId().':'.$entity->bundle());

      // Update the Group field value
      $entity->field_group->entity = $group;
      $entity->save();
    }

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Group changed');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityType() === 'node') {
      $access = $object->access('update', $account, TRUE)
        ->andIf($object->status->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options[0] = '- None -';
 
    // Load all group types
    $group_types = \Drupal::entityTypeManager()->getStorage('group_type')->loadMultiple();

    foreach ($group_types as $group_type) {
        // Get all groups of this type. E.g. bureau_office
        $grps = \Drupal::entityTypeManager()
            ->getStorage('group')
            ->loadByProperties(['type' => $group_type->id()]);

        $sub_options = [];
        foreach($grps as $group) {
          $sub_options[$group->id->value] = $group->label->value;
        }

        // Group options in the Select element
        $options[$group_type->label()] = $sub_options;
    }

    $form['new_group'] = array(
      '#title' => t('Select new group'),
      '#type' => 'select',
      '#description' => 'The selected items will be assigned to the new group. Choose "- None -" to remove the items from all groups.',
      '#options' => $options,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['new_group'] = $form_state->getValue('new_group');
  }
}