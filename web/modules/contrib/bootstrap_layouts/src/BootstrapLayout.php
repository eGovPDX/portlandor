<?php

namespace Drupal\bootstrap_layouts;

use Drupal\Component\Utility\DiffArray;
use Drupal\Component\Utility\NestedArray;

/**
 * Class BootstrapLayout.
 */
class BootstrapLayout {

  /**
   * The layout data.
   *
   * @var array
   */
  protected $data;

  /**
   * The original layout data, used to determine if layout data has changed.
   *
   * @var array
   */
  protected $original;

  /**
   * BootstrapLayout constructor.
   *
   * @param string $id
   *   The layout identifier.
   * @param array $regions
   *   The layout regions.
   * @param array $settings
   *   The layout settings.
   * @param string $path
   *   The path to the layout.
   */
  public function __construct($id, array $regions = [], array $settings = [], $path = NULL) {
    $this->data = [
      'id' => $id,
      'regions' => $regions,
      'settings' => $settings,
      'path' => $path,
    ];
    $this->original = $this->data;
  }

  /**
   * Indicates whether or not the layout data has changed.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasChanged() {
    return !!DiffArray::diffAssocRecursive($this->original, $this->data);
  }

  /**
   * Retrieves the layout identifier.
   *
   * @return string
   */
  public function getId() {
    return $this->data['id'];
  }

  /**
   * Retrieves the path to the layout, may not be set.
   *
   * @return string|null
   */
  public function getPath() {
    return $this->data['path'];
  }

  /**
   * Retrieves a specific layout region.
   *
   * @param string $name
   *   The layout region to retrieve.
   * @param mixed $default_value
   *   The default value to use if layout region does not exists.
   *
   * @return mixed
   *   The layout region value or $default_value if it does not exist.
   */
  public function getRegion($name, $default_value = NULL) {
    return isset($this->data['regions'][$name]) ? $this->data['regions'][$name] : $default_value;
  }

  /**
   * Retrieves all defined layout regions.
   *
   * @return array
   *   An associative array of layout regions, keyed by their machine name.
   */
  public function getRegions() {
    return $this->data['regions'];
  }

  /**
   * Retrieves a specific layout setting.
   *
   * @param string $name
   *   The layout setting name. Can be dot notation to indicate a deeper key in
   *   the settings array.
   * @param mixed $default_value
   *   The default value to use if layout setting does not exists.
   *
   * @return mixed
   *   The layout setting value or $default_value if it does not exist.
   */
  public function getSetting($name, $default_value = NULL) {
    $parts = explode('.', $name);
    if (count($parts) === 1) {
      return isset($this->data['settings'][$name]) ? $this->data['settings'][$name] : $default_value;
    }
    $value = NestedArray::getValue($this->data['settings'], $parts, $key_exists);
    return $key_exists ? $value : $default_value;
  }

  /**
   * Retrieves all defined layout settings.
   *
   * @return array
   *   An associative array of layout settings, keyed by their machine name.
   */
  public function getSettings() {
    return $this->data['settings'];
  }

  /**
   * Indicates if this layout is a Bootstrap Layouts layout.
   *
   * @return bool
   *   TRUE or FALSE
   *
   * @todo This seems backwards, maybe refactor?
   */
  public function isBootstrapLayout() {
    static $bootstrap_manager;
    if (!isset($bootstrap_manager)) {
      /** @var \Drupal\bootstrap_layouts\BootstrapLayoutsManager $bootstrap_manager */
      $bootstrap_manager = \Drupal::service('plugin.manager.bootstrap_layouts');
    }
    return $bootstrap_manager->isBootstrapLayout($this->data['id']);
  }

  /**
   * Sets the layout identifier.
   *
   * @param string $id
   *   The layout identifier.
   *
   * @return \Drupal\bootstrap_layouts\BootstrapLayout
   *   The current BootstrapLayout instance.
   */
  public function setId($id) {
    $this->data['id'] = $id;
    return $this;
  }

  /**
   * Sets the path to the layout.
   *
   * @param string $path
   *   The path to the layout.
   *
   * @return string|null
   */
  public function setPath($path) {
    $this->data['path'] = $path;
    return $this;
  }

  /**
   * Sets a specific layout region.
   *
   * @param string $name
   *   The layout region name.
   * @param mixed $value
   *   The layout region value.
   *
   * @return \Drupal\bootstrap_layouts\BootstrapLayout
   *   The current BootstrapLayout instance.
   */
  public function setRegion($name, $value = NULL) {
    $this->data['regions'][$name] = $value;
    return $this;
  }

  /**
   * Sets a specific layout setting.
   *
   * @param string $name
   *   The layout setting name. Can be dot notation to indicate a deeper key in
   *   the settings array.
   * @param mixed $value
   *   The layout setting value.
   *
   * @return \Drupal\bootstrap_layouts\BootstrapLayout
   *   The current BootstrapLayout instance.
   */
  public function setSetting($name, $value = NULL) {
    $parts = explode('.', $name);
    if (count($parts) === 1) {
      $this->data['settings'][$name] = $value;
    }
    else {
      NestedArray::setValue($this->data['settings'], $parts, $value);
    }
    return $this;
  }

  /**
   * Removes a layout region.
   *
   * @param string $name
   *   The layout region to remove.
   *
   * @return mixed
   *   The region that was removed.
   */
  public function unsetRegion($name) {
    $old = isset($this->data['regions'][$name]) ? $this->data['regions'][$name] : NULL;
    unset($this->data['regions'][$name]);
    return $old;
  }

  /**
   * Removes a layout setting.
   *
   * @param string $name
   *   The layout region to remove.
   *
   * @return mixed
   *   The setting that was removed.
   */
  public function unsetSetting($name) {
    $old = isset($this->data['settings'][$name]) ? $this->data['settings'][$name] : NULL;
    unset($this->data['settings'][$name]);
    return $old;
  }

}
