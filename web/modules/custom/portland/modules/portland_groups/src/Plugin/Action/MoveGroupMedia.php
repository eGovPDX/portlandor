<?php

namespace Drupal\portland_groups\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\groupmedia\AttachMediaToGroup;
use Drupal\media\MediaInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Move media to a different group.
 */
#[Action(
  id: 'vbo_move_group_media',
  label: new TranslatableMarkup('Move media to a different group (custom action)'),
  type: 'media',
)]
class MoveGroupMedia extends ViewsBulkOperationsActionBase implements PluginFormInterface, ContainerFactoryPluginInterface
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
  public function execute(?MediaInterface $media = NULL)
  {
    if (empty($media)) {
      return $this->t('No media specified.');
    }

    $old_group_id = $media->get('field_display_groups')->target_id;
    $new_group_id = $this->configuration['group_id'];
    $new_group = \Drupal::entityTypeManager()->getStorage('group')->load($new_group_id);
    $new_group_title = $new_group ? $new_group->label() : '';

    // Update the Displayed In Group field on the media entity.
    if ($media->hasField('field_display_groups')) {
      $old_display_groups = $media->get('field_display_groups')->getValue();
      // Filter out the old group and the new group from the list.
      $new_display_groups = array_filter($old_display_groups, function ($old_display_group) use ($old_group_id, $new_group_id) {
        return ($old_display_group['target_id'] != $old_group_id && $old_display_group['target_id'] != $new_group_id);
      });
      // Add the new group to the beginning of the list. The first group in the list is the primary group.
      array_unshift($new_display_groups, ['target_id' => $new_group_id]);
      $media->set('field_display_groups', $new_display_groups);
      $media->setRevisionLogMessage("Moved to group by bulk operation: $new_group_title");
      $media->save();
      // Group relationships are automatically synced after the media is saved. 
      // See _portland_groups_synchronize_entity_group_ownership() in portland_groups.module.
      return $this->t("Media moved to " . $new_group_title);
    }
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
    $user = \Drupal::currentUser();
    $is_admin = in_array('administrator', $user->getRoles());

    // If the user is a site admin, allow them to select any group.
    if ($is_admin) {
      $form['group_id'] = [
        '#title' => $this->t('Select the destination group'),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'group',
        '#required' => TRUE,
      ];
    } else {
      // If the user is not an admin, limit the selection to groups they are a member of.
      $form['group_id'] = [
        '#title' => $this->t('Select the destination group'),
        '#type' => 'entity_autocomplete',
        '#target_type' => 'group',
        '#required' => TRUE,
        '#selection_handler' => 'views',
        '#selection_settings' => [
          'view' => [
            'view_name' => 'assigned_groups',
            'display_name' => 'er_user_in_groups',
            'arguments' => [$user->id()],
          ],
          'match_operator' => 'CONTAINS'
        ],
      ];
    }
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
    /** @var \Drupal\media\MediaInterface $object */
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}
}
