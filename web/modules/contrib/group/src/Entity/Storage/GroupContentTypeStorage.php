<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for group content type entities.
 *
 * This extends the base storage class, adding required special handling for
 * loading group content type entities based on group type and plugin ID.
 */
class GroupContentTypeStorage extends ConfigEntityStorage implements GroupContentTypeStorageInterface {

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a GroupContentTypeStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, GroupContentEnablerManagerInterface $plugin_manager, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroupType(GroupTypeInterface $group_type) {
    return $this->loadByProperties(['group_type' => $group_type->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByContentPluginId($plugin_id) {
    return $this->loadByProperties(['content_plugin' => $plugin_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntityTypeId($entity_type_id) {
    $plugin_ids = [];
    
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    foreach ($this->pluginManager->getAll() as $plugin_id => $plugin) {
      if ($plugin->getEntityTypeId() === $entity_type_id) {
        $plugin_ids[] = $plugin_id;
      }
    }

    // If no responsible group content plugins were found, we return nothing.
    if (empty($plugin_ids)) {
      return [];
    }

    // Otherwise load all group content types being handled by gathered plugins.
    return $this->loadByContentPluginId($plugin_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function createFromPlugin(GroupTypeInterface $group_type, $plugin_id, array $configuration = []) {
    // Add the group type ID to the configuration.
    $configuration['group_type_id'] = $group_type->id();

    // Instantiate the plugin we are installing.
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $this->pluginManager->createInstance($plugin_id, $configuration);

    // Create the group content type using plugin generated info.
    $values = [
      'id' => $plugin->getContentTypeConfigId(),
      'label' => $plugin->getContentTypeLabel(),
      'description' => $plugin->getContentTypeDescription(),
      'group_type' => $group_type->id(),
      'content_plugin' => $plugin_id,
      'plugin_config' => $plugin->getConfiguration(),
    ];
    
    return $this->create($values);
  }

}
