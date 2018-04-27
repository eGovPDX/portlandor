<?php

namespace Drupal\ds;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Helper class that holds all the main Display Suite helper functions.
 */
class Ds {

  /**
   * Gets all Display Suite fields.
   *
   * @param string $entity_type
   *   The name of the entity.
   *
   * @return array
   *   Collection of fields.
   */
  public static function getFields($entity_type) {
    static $static_fields;

    if (!isset($static_fields[$entity_type])) {
      foreach (\Drupal::service('plugin.manager.ds')->getDefinitions() as $plugin_id => $plugin) {
        // Needed to get derivatives working.
        $plugin['plugin_id'] = $plugin_id;
        if (is_array($plugin['entity_type'])) {
          foreach ($plugin['entity_type'] as $plugin_entity_type) {
            $static_fields[$plugin_entity_type][$plugin_id] = $plugin;
          }
        }
        else {
          $static_fields[$plugin['entity_type']][$plugin_id] = $plugin;
        }
      }
    }

    return isset($static_fields[$entity_type]) ? $static_fields[$entity_type] : [];
  }

  /**
   * Gets the value for a Display Suite field.
   *
   * @param string $key
   *   The key of the field.
   * @param array $field
   *   The configuration of a DS field.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The current entity.
   * @param string $view_mode
   *   The name of the view mode.
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The entity display object.
   * @param array $build
   *   The current built of the entity.
   *
   * @return \Drupal\ds\Plugin\DsField\DsFieldInterface
   *   Field instance.
   */
  public static function getFieldInstance($key, array $field, EntityInterface $entity, $view_mode, EntityDisplayInterface $display, array $build = []) {
    $configuration = [
      'field' => $field,
      'field_name' => $key,
      'entity' => $entity,
      'build' => $build,
      'view_mode' => $view_mode,
    ];

    // Load the plugin.
    /* @var $field_instance \Drupal\ds\Plugin\DsField\DsFieldInterface */
    $field_instance = \Drupal::service('plugin.manager.ds')->createInstance($field['plugin_id'], $configuration);

    /* @var $display \Drupal\Core\Entity\Display\EntityDisplayInterface */
    if ($field_settings = $display->getThirdPartySetting('ds', 'fields')) {
      $settings = isset($field_settings[$key]['settings']) ? $field_settings[$key]['settings'] : [];
      // Unset field template settings.
      if (isset($settings['ft'])) {
        unset($settings['ft']);
      }

      $field_instance->setConfiguration($settings);
    }

    return $field_instance;
  }

  /**
   * Gets Display Suite layouts.
   *
   * @return \Drupal\Core\Layout\LayoutDefinition[]
   *   A list of layouts.
   */
  public static function getLayouts() {
    static $layouts = FALSE;

    if (!$layouts) {
      // This can be called before ds_update_8003() has run. If that is the case
      // return an empty array and don't static cache anything.
      if (!\Drupal::hasService('plugin.manager.core.layout')) {
        return [];
      }
      $layouts = \Drupal::service('plugin.manager.core.layout')->getDefinitions();
    }

    return $layouts;
  }

  /**
   * Gets a display for a given entity.
   *
   * @param string $entity_type
   *   The name of the entity.
   * @param string $bundle
   *   The name of the bundle.
   * @param string $view_mode
   *   The name of the view mode.
   * @param bool $fallback
   *   Whether to fallback to default or not.
   *
   * @return array|bool
   *   The display.
   */
  public static function getDisplay($entity_type, $bundle, $view_mode, $fallback = TRUE) {
    /* @var $entity_display \Drupal\Core\Entity\Display\EntityDisplayInterface */
    $entity_manager = \Drupal::entityTypeManager();
    $entity_display = $entity_manager->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.' . $view_mode);
    if ($entity_display) {
      $overridden = $entity_display->status();
    }
    else {
      $overridden = FALSE;
    }

    if ($entity_display) {
      return $entity_display;
    }

    // In case $view_mode is not found, check if we have a default layout,
    // but only if the view mode settings aren't overridden for this view mode.
    if ($view_mode != 'default' && !$overridden && $fallback) {
      /* @var $entity_default_display \Drupal\Core\Entity\Display\EntityDisplayInterface */
      $entity_manager = \Drupal::entityTypeManager();
      $entity_default_display = $entity_manager->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.default');
      if ($entity_default_display) {
        return $entity_default_display;
      }
    }

    return FALSE;
  }

  /**
   * Checks if we can go on with Display Suite.
   *
   * In some edge cases, a view might be inserted into the view of an entity, in
   * which the same entity is available as well. This is simply not possible and
   * will lead to infinite loops, so you can temporarily disable DS completely
   * by setting this variable, either from code or visit the UI through
   * admin/structure/ds/emergency.
   */
  public static function isDisabled() {
    return \Drupal::state()->get('ds.disabled', FALSE);
  }

  /**
   * Gets all Display Suite field layouts options.
   *
   * Mainly used by select fields.
   *
   * @return array
   *   List of field layouts.
   */
  public static function getFieldLayoutOptions() {
    $options = [];
    foreach (\Drupal::service('plugin.manager.ds.field.layout')->getDefinitions() as $plugin_id => $plugin) {
      $options[$plugin_id] = $plugin['title'];
    }
    return $options;
  }

  /**
   * Utility function to return CSS classes.
   */
  public static function getClasses($name = 'region') {
    static $classes = [];

    if (!isset($classes[$name])) {
      $classes[$name] = [];
      $custom_classes = \Drupal::config('ds.settings')->get('classes.' . $name);
      if (!empty($custom_classes)) {
        $classes[$name][''] = t('None');
        foreach ($custom_classes as $value) {
          $classes_splitted = explode("|", $value);
          $key = trim($classes_splitted[0]);
          $friendly_name = isset($classes_splitted[1]) ? trim($classes_splitted[1]) : $key;
          $classes[$name][Html::escape($key)] = $friendly_name;
        }
      }
      // Prevent the name from being changed.
      $name_clone = $name;
      \Drupal::moduleHandler()->alter('ds_classes', $classes[$name], $name_clone);
    }

    return $classes[$name];
  }

}
