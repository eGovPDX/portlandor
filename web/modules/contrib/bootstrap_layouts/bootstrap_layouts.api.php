<?php

/**
 * @file
 * API hooks/callbacks for the Bootstrap Layouts module.
 */

use Drupal\Component\Utility\Unicode;

/**
 * Alters the list of available classes that can be used in Bootstrap layouts.
 *
 * @param array $classes
 *   An associative array groups keyed by group machine name, containing
 *   another associative array containing key/value pairs where the class
 *   is the key and a human readable label is the the value.
 * @param $groups
 *   An associative array groups labels keyed by group machine name.
 *
 * @see \Drupal\bootstrap_layouts\BootstrapLayoutsManager::getClassOptions
 */
function hook_bootstrap_layouts_class_options_alter(&$classes, &$groups) {
  // Add theme specific classes.
  $groups['my_theme'] = t('My Theme');
  foreach (['top', 'middle', 'bottom'] as $style) {
    $classes['my_theme']["section-$style"] = t('Section: @style', ['@style' => Unicode::ucfirst($style)]);
  }
}
