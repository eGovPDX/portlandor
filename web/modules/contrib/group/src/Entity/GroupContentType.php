<?php

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group content type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_content_type",
 *   label = @Translation("Group content type"),
 *   label_singular = @Translation("group content type"),
 *   label_plural = @Translation("group content types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group content type",
 *     plural = "@count group content types"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupContentTypeStorage",
 *     "access" = "Drupal\group\Entity\Access\GroupContentTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupContentTypeForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupContentTypeForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupContentTypeDeleteForm"
 *     },
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "content_type",
 *   bundle_of = "group_content",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "group_type",
 *     "content_plugin",
 *     "plugin_config",
 *   }
 * )
 */
class GroupContentType extends ConfigEntityBundleBase implements GroupContentTypeInterface {

  /**
   * The machine name of the group content type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group content type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the group content type.
   *
   * @var string
   */
  protected $description;

  /**
   * The group type ID for the group content type.
   *
   * @var string
   */
  protected $group_type;

  /**
   * The group content enabler plugin ID for the group content type.
   *
   * @var string
   */
  protected $content_plugin;

  /**
   * The group content enabler plugin configuration for group content type.
   *
   * @var array
   */
  protected $plugin_config = [];

  /**
   * The content enabler plugin instance.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerInterface
   */
  protected $pluginInstance;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return GroupType::load($this->getGroupTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->group_type;
  }

  /**
   * Returns the content enabler plugin manager.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   *   The group content plugin manager.
   */
  protected function getContentEnablerManager() {
    return \Drupal::service('plugin.manager.group_content_enabler');
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPlugin() {
    if (!isset($this->pluginInstance)) {
      $configuration = $this->plugin_config;
      $configuration['group_type_id'] = $this->getGroupTypeId();
      $this->pluginInstance = $this->getContentEnablerManager()->createInstance($this->getContentPluginId(), $configuration);
    }
    return $this->pluginInstance;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPluginId() {
    return $this->content_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function updateContentPlugin(array $configuration) {
    $this->plugin_config = $configuration;
    $this->save();

    // Make sure people get a fresh local plugin instance.
    $this->pluginInstance = NULL;

    // Make sure people get a freshly configured plugin collection.
    $this->getContentEnablerManager()->clearCachedGroupTypeCollections($this->getGroupType());
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByContentPluginId($plugin_id) {
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('group_content_type');
    return $storage->loadByContentPluginId($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEntityTypeId($entity_type_id) {
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('group_content_type');
    return $storage->loadByEntityTypeId($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      // When a new GroupContentType is saved, we clear the views data cache to
      // make sure that all of the views data which relies on group content
      // types is up to date.
      if (\Drupal::moduleHandler()->moduleExists('views')) {
        \Drupal::service('views.views_data')->clear();
      }

      // Run the post install tasks on the plugin.
      $this->getContentPlugin()->postInstall();

      // We need to reset the plugin ID map cache as it will be out of date now.
      $this->getContentEnablerManager()->clearCachedPluginMaps();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // When a GroupContentType is deleted, we clear the views data cache to make
    // sure that all of the views data which relies on group content types is up
    // to date.
    if (\Drupal::moduleHandler()->moduleExists('views')) {
      \Drupal::service('views.views_data')->clear();
    }

    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.group_content_enabler');

    // We need to reset the plugin ID map cache as it will be out of date now.
    $plugin_manager->clearCachedPluginMaps();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // By adding the group type as a dependency, we ensure the group content
    // type is deleted along with the group type.
    $this->addDependency('config', $this->getGroupType()->getConfigDependencyName());

    // Add the dependencies of the responsible content enabler plugin.
    $this->addDependencies($this->getContentPlugin()->calculateDependencies());
  }

}
