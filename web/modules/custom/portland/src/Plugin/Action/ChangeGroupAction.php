<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;

/**
 * Some description.
 *
 * @Action(
 *   id = "change_group_action",
 *   label = @Translation("Change parent group of Content (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class ChangeGroupAction extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /*
    The user intention is 
    1. Change the group the content belongs to. 
    2. Update the group field value to be the same as 1.

    if old != new
      if new != NULL && old != NULL
        remove from old group and add to new
      else new != NULL && old == NULL
        add to new group
      else new == NULL && old != NULL
        remove from old group
    */
    $new_group_id = $this->configuration['new_group']; //string
    $new_group = ($new_group_id == 0) ? NULL : Group::load($new_group_id);
    $new_group_type = ($new_group == NULL) ? NULL : $new_group->getGroupType()->id();
    $old_group_id_array = self::getGroupIdsByEntity($entity);
    $old_group_id = (count($old_group_id_array) > 0) ? $old_group_id_array[0] : 0;

    // Services can only be added to Bureaus and Programs.
    if( $entity->bundle() == 'city_service' && 
      ($new_group_type != 'bureau_office' && $new_group_type != 'program') ) 
    {
      // Make sure Group field is the same as old group
      if( $entity->hasField('field_group') ) $entity->field_group->entity = $old_group;
      $entity->save();
      return $this->t('Service can only be in Bureaus/Offices or Programs.');
    }

    if($old_group_id != $new_group_id) {
      if($old_group_id != 0) {
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
      }
      if($new_group_id != 0) {
        // Add to new group
        $new_group->addContent($entity, 'group_'.$entity->getEntityTypeId().':'.$entity->bundle());
      }
    }

    if( $entity->hasField('field_group') ) $entity->field_group->entity = $new_group;
    $entity->save();

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

  // Get the parent group IDs of an entity
  // https://drupal.stackexchange.com/questions/238755/how-to-get-group-ids-by-ids-of-elements-of-group-content
  public static function getGroupIdsByEntity($entity) {
    if($entity == NULL) return [];
    $group_ids = [];

    $group_contents = GroupContent::loadByEntity($entity);
    foreach ($group_contents as $group_content) {
        $group_ids[] = $group_content->getGroup()->id();
    }

    return $group_ids;
  }
}