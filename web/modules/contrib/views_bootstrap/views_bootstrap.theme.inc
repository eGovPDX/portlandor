<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\views_bootstrap\ViewsBootstrap;
use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for views accordion templates.
 *
 * Default template: views-bootstrap-accordion.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_accordion(array &$vars) {
  $view = $vars['view'];
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $panel_title_field = $view->style_plugin->options['panel_title_field'];
  $vars['attributes']['class'][] = 'panel-group';
  if ($panel_title_field) {
    foreach ($vars['rows'] as $id => $row) {
      $vars['rows'][$id] = [];
      $vars['rows'][$id]['content'] = $row;
      $vars['rows'][$id]['title'] = $view->style_plugin->getFieldValue($id, $panel_title_field);
    }
  }
  else {
    // @TODO: This would be better as valdiation errors on the style plugin options form.
    drupal_set_message(t('@style style will not display without the "@field" setting.',
      [
        '@style' => $view->style_plugin->definition['title'],
        '@field' => 'Panel title',
      ]
    ), 'warning');
  }
  // @TODO: Make sure that $vars['rows'] is rendered array.
  // @SEE: Have a look template_preprocess_views_view_unformatted()
  // and views-view-unformatted.html.twig
}

/**
 * Prepares variables for views carousel template.
 *
 * Default template: views-bootstrap-carousel.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_carousel(array &$vars) {
  $view = $vars['view'];
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $vars['attributes']['class'][] = 'views-bootstrap-media-object';
  $vars['attributes']['class'][] = 'media-list';

  // Carousel options.
  $vars['interval'] = $view->style_plugin->options['interval'];
  $vars['navigation'] = $view->style_plugin->options['navigation'];
  $vars['indicators'] = $view->style_plugin->options['indicators'];
  $vars['pause'] = $view->style_plugin->options['pause'] ? 'hover' : FALSE;
  $vars['wrap'] = $view->style_plugin->options['wrap'];

  // Carousel rows.
  $image = $view->style_plugin->options['image'];
  $title = $view->style_plugin->options['title'];
  $description = $view->style_plugin->options['description'];
  $fieldLabels = $view->display_handler->getFieldLabels(TRUE);

  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [];
    $vars['rows'][$id]['image'] = $view->style_plugin->getField($id, $image);
    $vars['rows'][$id]['title'] = $view->style_plugin->getField($id, $title);
    $vars['rows'][$id]['description'] = $view->style_plugin->getField($id, $description);
    // Add any additional fields to result.
    foreach (array_keys($fieldLabels) as $label) {
      if (!in_array($label, [$image, $title, $description])) {
        $vars['rows'][$id][$label] = $view->style_plugin->getField($id, $label);
      }
    }
  }

}

/**
 * Prepares variables for views grid templates.
 *
 * Default template: views-bootstrap-grid.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_grid(array &$vars) {
  $view = $vars['view'];
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $vars['attributes']['class'][] = 'grid';
  $options = $view->style_plugin->options;
  $options['automatic_width'] = ['default' => TRUE];
  $horizontal = ($options['alignment'] === 'horizontal');

  foreach (['xs', 'sm', 'md', 'lg'] as $size) {
    $vars["col_" . $size] = $options["col_" . $size];
    // Get the value from the size sting.
    $vars['sizes'][$size] = ViewsBootstrap::getColSize($options["col_" . $size]);
  }
  $vars['options'] = $options;

}

/**
 * Prepares variables for views list group templates.
 *
 * Default template: views-bootstrap-list-group.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_list_group(array &$vars) {
  $view = $vars['view'];
  $options = $view->style_plugin->options;
  $vars['id'] = ViewsBootstrap::getUniqueId($view);
  $vars['attributes']['class'][] = 'views-bootstrap-list-group';
  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [];
    $vars['rows'][$id]['content'] = $row;
    $vars['rows'][$id]['title'] = $vars['view']->style_plugin->getField($id, $options['title_field']);
  }

}

/**
 * Prepares variables for views media object templates.
 *
 * Default template: views-bootstrap-media-object.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_media_object(array &$vars) {
  $vars['id'] = ViewsBootstrap::getUniqueId($vars['view']);
  $image_class = $vars['view']->style_plugin->options['image_class'];
  $image_field = $vars['view']->style_plugin->options['image_field'];
  $heading_field = $vars['view']->style_plugin->options['heading_field'];
  $body_field = $vars['view']->style_plugin->options['body_field'];

  foreach ($vars['rows'] as $id => $row) {
    $vars['rows'][$id] = [];
    $vars['classes'][$id] .= ' media-object';
    $vars['rows'][$id]['image_class'] = $image_class;
    $vars['rows'][$id]['image'] = $vars['view']->style_plugin->getField($id, $image_field);
    $vars['rows'][$id]['heading'] = $vars['view']->style_plugin->getField($id, $heading_field);
    $vars['rows'][$id]['body'] = $vars['view']->style_plugin->getField($id, $body_field);
  }
}

/**
 * Prepares variables for views tab templates.
 *
 * Default template: views-bootstrap-tab.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_tab(array &$vars) {
  $vars['id'] = ViewsBootstrap::getUniqueId($vars['view']);
  $view = $vars['view'];
  $tab_field = $view->style_plugin->options['tab_field'];
  $vars['tab_type'] = $view->style_plugin->options['tab_type'];
  $vars['justified'] = $view->style_plugin->options['justified'];

  // Get tabs.
  if ($tab_field) {
    if (isset($view->field[$tab_field])) {
      foreach (array_keys($vars['rows']) as $key) {
        $vars['tabs'][$key] = $view->style_plugin->getFieldValue($key, $tab_field);
      }
    }
    foreach ($vars['rows'] as $id => $row) {
      $vars['rows'][$id] = array();
      $vars['rows'][$id]['content'] = $row;
      $vars['rows'][$id]['attributes'] = new Attribute();
      if ($row_class = $view->style_plugin->getRowClass($id)) {
        $vars['rows'][$id]['attributes']->addClass($row_class);
      }
    }
  }
  else {
    // @TODO: This would be better as valdiation errors on the style plugin options form.
    drupal_set_message(t('@style style will not display without the "@field" setting.',
      [
        '@style' => $view->style_plugin->definition['title'],
        '@field' => 'Tab title',
      ]
    ), 'warning');
  }
}

/**
 * Prepares variables for views table templates.
 *
 * Default template: views-bootstrap-table.html.twig.
 *
 * @param array $vars
 *   An associative array containing:
 *   - view: A ViewExecutable object.
 *   - rows: The raw row data.
 */
function template_preprocess_views_bootstrap_table(array &$vars) {
  $vars['responsive'] = $vars['view']->style_plugin->options['responsive'];
  $vars['attributes']['class'][] = 'table';
  foreach (array_filter($vars['view']->style_plugin->options['bootstrap_styles']) as $style) {
    $vars['attributes']['class'][] = 'table-' . $style;
  }
}

