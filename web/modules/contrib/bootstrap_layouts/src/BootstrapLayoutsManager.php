<?php

namespace Drupal\bootstrap_layouts;

use Drupal\bootstrap_layouts\Plugin\Layout\BootstrapLayoutsBase;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Layout\LayoutPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BootstrapLayoutsManager
 */
class BootstrapLayoutsManager extends BootstrapLayoutsPluginManager {

  /**
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutManager;

  /**
   * @var \Drupal\bootstrap_layouts\BootstrapLayoutsUpdateManager
   */
  protected $updateManager;

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
   * @param \Drupal\Core\Layout\LayoutPluginManager $layout_manager
   *   The Layout Manager.
   * @param \Drupal\bootstrap_layouts\BootstrapLayoutsUpdateManager $update_manager
   *   The Bootstrap Layouts update manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, ThemeManagerInterface $theme_manager, LayoutPluginManager $layout_manager, BootstrapLayoutsUpdateManager $update_manager) {
    parent::__construct($namespaces, $cache_backend, $module_handler, $theme_handler, $theme_manager, 'Drupal\bootstrap_layouts\Plugin\BootstrapLayouts\BootstrapLayoutsHandlerInterface', 'Drupal\bootstrap_layouts\Annotation\BootstrapLayoutsHandler');
    $this->layoutManager = $layout_manager;
    $this->updateManager = $update_manager;
    $this->alterInfo('bootstrap_layouts_handler_info');
    $this->setCacheBackend($cache_backend, 'bootstrap_layouts_handler_info');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('container.namespaces'),
      $container->get('cache.discovery'),
      $container->get('module_handler'),
      $container->get('theme_handler'),
      $container->get('theme.manager'),
      $container->get('plugin.manager.core.layout'),
      $container->get('plugin.manager.bootstrap_layouts.update')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    // The handler plugin identifiers represent the module or theme that
    // implements said layouts. Remove any handler plugins that not installed.
    foreach (array_keys($definitions) as $provider) {
      if (!$this->providerExists($provider)) {
        unset($definitions[$provider]);
      }
      else {
        // Attempt to retrieve the theme human readable label first.
        try {
          $label = $this->themeHandler->getName($provider);
        }
        // Otherwise attempt to retrieve the module human readable label.
        catch (\Exception $e) {
          $label = $this->moduleHandler->getName($provider);
        }
        $definitions[$provider]['label'] = $label;
      }
    }
    return $definitions;
  }

  /**
   * Retrieves classes that can be used in Bootstrap layouts as select options.
   *
   * @return array
   *   An associative array of grouped classes to be used in select options.
   */
  public function getClassOptions() {
    static $classes;

    if (!isset($classes)) {
      $utility = [];
      $col = [];
      $hidden = [];
      $visible = [];
      $bg = [];
      $text_color = [];
      $text_alignment = [
        'text-left' => $this->t('Left'),
        'text-right' => $this->t('Right'),
        'text-center' => $this->t('Center'),
        'text-justify' => $this->t('Justify'),
        'text-nowrap' => $this->t('No Wrap'),
      ];
      $text_transformation = [
        'text-lowercase' => $this->t('Lowercase'),
        'text-uppercase' => $this->t('Uppercase'),
        'text-capitalize' => $this->t('Capitalize'),
      ];

      // Utility.
      $utility['clearfix'] = $this->t('Clear Fix');
      $utility['row'] = $this->t('Row');

      $sizes = [
        'xs' => $this->t('Extra Small'),
        'sm' => $this->t('Small'),
        'md' => $this->t('Medium'),
        'lg' => $this->t('Large'),
      ];

      foreach ($sizes as $size => $size_label) {
        $hidden["hidden-$size"] = $size_label;
        $visible["visible-$size"] = $size_label;
        foreach (range(1, 12) as $column) {
          $col["col-$size-$column"] = $this->t('@size: @column', [
            '@size' => $size_label,
            '@column' => $column,
          ]);
        }
      }

      // Background/text color classes.
      foreach (['primary', 'danger', 'info', 'warning', 'success'] as $type) {
        $bg["bg-$type"] = $this->t('@type', ['@type' => Unicode::ucfirst($type)]);
        $text_color["text-$type"] = $this->t('@type', ['@type' => Unicode::ucfirst($type)]);
      }
      $text_color['text-muted'] = $this->t('Muted');

      // Groups.
      $groups = [
        'utility' => $this->t('Utility'),
        'columns' => $this->t('Columns'),
        'hidden' => $this->t('Hidden'),
        'visible' => $this->t('Visible'),
        'background' => $this->t('Background'),
        'text_alignment' => $this->t('Text alignment'),
        'text_color' => $this->t('Text color'),
        'text_transformation' => $this->t('Text transformation'),
      ];

      // Classes, keyed by group.
      $classes = [
        'utility' => $utility,
        'columns' => $col,
        'hidden' => $hidden,
        'visible' => $visible,
        'background' => $bg,
        'text_alignment' => $text_alignment,
        'text_color' => $text_color,
        'text_transformation' => $text_transformation,
      ];

      // Invokes hook_bootstrap_layouts_class_options_alter().
      $this->moduleHandler->alter('bootstrap_layouts_class_options', $classes, $groups);
      $this->themeManager->alter('bootstrap_layouts_class_options', $classes, $groups);

      // Render the group labels and use them for optgroup values.
      $grouped = [];
      foreach ($classes as $group => $data) {
        $group = (string) (isset($groups[$group]) ? $groups[$group] : $group);
        $grouped[$group] = $data;
      }
      $classes = $grouped;
    }

    return $classes;
  }

  /**
   * Indicates if provided layout identifier is a Bootstrap Layouts layout.
   *
   * @param string $id
   *   The layout identifier to test.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isBootstrapLayout($id) {
    static $layouts;
    if (!isset($layouts)) {
      $layouts = [];
      foreach (array_keys($this->layoutManager->getDefinitions()) as $layout_id) {
        $plugin = $this->layoutManager->createInstance($layout_id);
        if ($plugin instanceof BootstrapLayoutsBase) {
          $layouts[] = $layout_id;
        }
      }
    }
    return in_array($id, $layouts);
  }

  /**
   * Retrieves all available handler instances.
   *
   * @return \Drupal\bootstrap_layouts\Plugin\BootstrapLayouts\BootstrapLayoutsHandlerInterface[]
   */
  public function getHandlers() {
    $instances = [];
    foreach (array_keys($this->getDefinitions()) as $plugin_id) {
      $instances[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $instances;
  }

  /**
   * Runs update(s) for a specific schema version.
   *
   * @param int $schema
   *   The schema version to update.
   * @param bool $display_messages
   *   Flag determining whether a message will be displayed indicating whether
   *   the layout was processed successfully or not.
   */
  public function update($schema, $display_messages = TRUE) {
    $handlers = $this->getHandlers();
    $data = [];
    foreach ($this->updateManager->getUpdates($schema) as $update) {
      // See if there's an adjoining YML file with the update plugin.
      $r = new \ReflectionClass($update);
      $data_paths = [dirname($r->getFileName()), $update->getPath()];

      // Merge in any update data.
      foreach ($data_paths as $path) {
        $file = "$path/bootstrap_layouts.update.$schema.yml";
        if (file_exists($file) && ($yaml = Yaml::decode(file_get_contents($file)))) {
          $data = NestedArray::mergeDeep($data, $yaml);
        }
      }

      // Perform the update.
      $update->update($this, $data, $display_messages);

      // Process any existing layouts after the update.
      foreach ($handlers as $handler_id => $handler) {
        foreach ($handler->loadInstances() as $storage_id => $layout) {
          $update->processExistingLayout($layout, $data, $display_messages);

          // Determine if the layout has changed and then save it.
          if ($layout->hasChanged()) {
            try {
              $handler->saveInstance($storage_id, $layout);
              if ($display_messages) {
                \drupal_set_message($this->t('Successfully updated the existing Bootstrap layout found in "@id".', ['@id' => $storage_id]));
              }
            } catch (\Exception $e) {
              \drupal_set_message($this->t('Unable to update the existing Bootstrap layout found in "@id":', ['@id' => $storage_id]), 'error');
              \drupal_set_message($e->getMessage(), 'error');
            }
          }
        }
      }
    }
  }

}
