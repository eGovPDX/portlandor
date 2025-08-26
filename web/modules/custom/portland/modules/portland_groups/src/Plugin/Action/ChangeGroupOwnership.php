<?php

namespace Drupal\portland_groups\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\groupmedia\AttachMediaToGroup;
use Drupal\media\MediaInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Move media to a different group.
 */
#[Action(
  id: 'vbo_change_group_ownership',
  label: new TranslatableMarkup('Change the group ownership of content or media (custom action)'),
  type: '', // Leave this blank to support both node and media.
)]
class ChangeGroupOwnership extends ViewsBulkOperationsActionBase implements PluginFormInterface, ContainerFactoryPluginInterface
{
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Attach media tp group service.
   *
   * @var \Drupal\groupmedia\AttachMediaToGroup
   */
  protected $attachMediaToGroup;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\groupmedia\AttachMediaToGroup $attach_media_to_group
   *   Attach media tp group service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AttachMediaToGroup $attach_media_to_group,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->attachMediaToGroup = $attach_media_to_group;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('groupmedia.attach_group')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(?FieldableEntityInterface $entity = NULL)
  {
    if (empty($entity)) {
      return $this->t('No entity specified.');
    }

    if (!$entity->hasField('field_display_groups')) {
      return $this->t('Entity does not have a Displayed In Group field.');
    }

    $old_group_id = $entity->get('field_display_groups')->target_id;
    $new_group_id = $this->configuration['group_id'];
    if ($old_group_id == $new_group_id) {
      return $this->t('Entity is already in the selected group.');
    }

    $old_group = \Drupal::entityTypeManager()->getStorage('group')->load($old_group_id);
    if (empty($old_group)) {
      return $this->t("Cannot find the original primary group with ID: $old_group_id");
    }
    $old_group_title = $old_group->label();
    $new_group = \Drupal::entityTypeManager()->getStorage('group')->load($new_group_id);
    if (empty($new_group)) {
      return $this->t("Cannot find the destination primary group with ID: $new_group_id");
    }
    $new_group_title = $new_group->label();

    // Update the Displayed In Group field on the entity.
    $old_display_groups = $entity->get('field_display_groups')->getValue();
    // Filter out the old group and the new group from the list.
    $new_display_groups = array_filter($old_display_groups, function ($old_display_group) use ($old_group_id, $new_group_id) {
      return ($old_display_group['target_id'] != $old_group_id && $old_display_group['target_id'] != $new_group_id);
    });
    // Add the new group to the beginning of the list. The first group in the list is the primary group.
    array_unshift($new_display_groups, ['target_id' => $new_group_id]);
    $entity->set('field_display_groups', $new_display_groups);
    if ($entity instanceof RevisionableInterface) {
      $entity->setNewRevision(TRUE);
    }
    if ($entity instanceof RevisionLogInterface) {
      $entity->setRevisionLogMessage("Bulk operation changed group ownership from $old_group_title ($old_group_id) to $new_group_title ($new_group_id).");
    }
    $entity->save();
    // Group relationships are automatically synced after the media is saved. 
    // See _portland_groups_synchronize_entity_group_ownership() in portland_groups.module.
    return $this->t("Changed group ownership to " . $new_group_title);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
      'group_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $form['group_id'] = [
      '#title' => $this->t('Select the destination group'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'group',
      '#required' => TRUE,
      '#selection_handler' => 'default:group_membership_filter',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    $this->configuration['group_id'] = $form_state->getValue('group_id');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    if ($object instanceof \Drupal\Core\Entity\EntityInterface) {
      $entity_type = $object->getEntityTypeId();
      $allowed_types = ['node', 'media'];

      if (in_array($entity_type, $allowed_types)) {
        // Check update access for the entity.
        return $object->access('update', $account, $return_as_object);
      }
    }

    return $return_as_object ? AccessResult::forbidden() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}
}
