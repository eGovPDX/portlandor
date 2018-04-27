<?php

namespace Drupal\bootstrap_layouts;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

class BootstrapLayoutsUpdateManager extends BootstrapLayoutsPluginManager {

  /**
   * Constructs a new \Drupal\bootstrap_layouts\BootstrapLayoutsManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme manager used to invoke the alter hook with.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager used to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ThemeManagerInterface $theme_manager) {
    parent::__construct($namespaces, $cache_backend, $module_handler, $theme_handler, $theme_manager, 'Drupal\bootstrap_layouts\Plugin\BootstrapLayouts\BootstrapLayoutsUpdateInterface', 'Drupal\bootstrap_layouts\Annotation\BootstrapLayoutsUpdate');
    $this->alterInfo('bootstrap_layouts_update_info');
    $this->setCacheBackend($cache_backend, 'bootstrap_layouts_update_info');
  }

  /**
   * Retrieves the update plugins for a specific schema version.
   *
   * @param int $schema
   *   The update schema version to retrieve.
   *
   * @return \Drupal\bootstrap_layouts\Plugin\BootstrapLayouts\BootstrapLayoutsUpdateInterface[]
   *   An array of update plugins, keyed by their plugin id.
   */
  public function getUpdates($schema) {
    $updates = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if (isset($definition['schema']) && $definition['schema'] == $schema) {
        $updates[$plugin_id] = $this->createInstance($plugin_id);
      }
    }
    return $updates;
  }

}
