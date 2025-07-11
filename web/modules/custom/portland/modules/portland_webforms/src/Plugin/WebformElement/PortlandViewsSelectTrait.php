<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\views\Views;
use Drupal\webform\Utility\WebformOptionsHelper;

trait PortlandViewsSelectTrait {
  public static function setOptions(array &$element, array $settings = []) {
    if (!empty($element['#options'])) {
      return;
    }

    $view_id = $element['#view_id'] ?? '';
    $display_id = $element['#display_id'] ?? '';
    $label_field = $element['#label_field'] ?? '';
    $value_field = $element['#value_field'] ?? '';

    $options = [];
    if ($view_id && $display_id && $label_field && $value_field) {
      $view = Views::getView($view_id);
      if ($view && $view->access($display_id)) {
        $view->setDisplay($display_id);
        $view->preExecute();
        $view->render();
        $label_field_handler = $view->field[$label_field];
        $value_field_handler = $view->field[$value_field];
        foreach ($view->result as $row_index => $row) {
          $label = str_replace("\n", '', $label_field_handler->tokenizeValue((string) $label_field_handler->render($row), $row_index));
          $value = html_entity_decode($value_field_handler->tokenizeValue((string) $value_field_handler->render($row), $row_index));
          if ($value !== '') {
            $options[$value] = $label;
          }
        }
      }
    }

    $options = WebformOptionsHelper::stripTagsOptions($options);
    $options = WebformOptionsHelper::decodeOptions($options);

    $element['#options'] = $options;
    \Drupal::service('renderer')->addCacheableDependency($element, $view->storage);
  }
}
