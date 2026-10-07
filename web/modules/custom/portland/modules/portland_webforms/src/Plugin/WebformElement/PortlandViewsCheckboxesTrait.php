<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\views\Views;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformSubmissionInterface;

trait PortlandViewsCheckboxesTrait {
  private const CACHE_PREFIX = 'portland_webforms:views_checkboxes:';

  private static function renderViewFields(array $element) {
    $view_id = $element['#view_id'] ?? '';
    $display_id = $element['#display_id'] ?? '';
    $field_data = [];
    if ($view_id && $display_id) {
      $cache = \Drupal::cache();
      $cache_id = self::CACHE_PREFIX . hash('sha256', $view_id . $display_id);
      $cache_result = $cache->get($cache_id);
      if ($cache_result && !empty($cache_result->data)) {
        $field_data = $cache_result->data;
      }
      else {
        $view = Views::getView($view_id);
        if ($view && $view->access($display_id)) {
          $view->setDisplay($display_id);
          $view->preExecute();
          $view->render();

          foreach ($view->result as $row_index => $row) {
            foreach ($view->field as $field_id => $field_handler) {
              $field_data[$row_index][$field_id] = html_entity_decode(str_replace("\n", '', $field_handler->tokenizeValue((string) $field_handler->render($row), $row_index)));
            }
          }

          \Drupal::service('renderer')->addCacheableDependency($element, $view->storage);
        }

        // Cache the view data for a short period to avoid repeated queries when multiple elements in one form are using the same view.
        // Built-in views caching could be relied on, but this is some extra safety to prevent rate limits for uncached external API views.
        $cache->set($cache_id, $field_data, time() + (int) ($element['#cache_ttl_sec'] ?? 10));
      }
    }

    return $field_data;
  }

  /**
   * Handle when the format is a field from a view, e.g. `webform_submission:values:my_checkboxes:cell_2`.
   * If the format is not a recognized field from the view, this function will return null and the caller should fallback to normal handling.
   */
  protected function getFieldValuesFromFormat(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    if (!empty($format) && $format !== 'value' && $format !== 'raw') {
      $view_id = $element['#view_id'] ?? '';
      $display_id = $element['#display_id'] ?? '';
      $view = Views::getView($view_id);
      if ($view && $view->access($display_id)) {
        $view->setDisplay($display_id);
        $fields = $view->getDisplay()->getOption('fields') ?? [];
        if (array_key_exists($format, $fields)) {
          $field_data = self::renderViewFields($element);
          $value_field_id = $element['#value_field'] ?? '';
          $value = $this->getValue($element, $webform_submission, $options);

          $selected_values = is_array($value)
            ? array_values(array_unique(array_filter($value, fn($v) => $v !== 0 && $v !== '' && $v !== NULL)))
            : [$value];

          $output = [];
          foreach ($selected_values as $selected_value) {
            // we're currently making the assumption that there aren't any duplicate values
            $value_row = array_find($field_data, fn($row) => ($row[$value_field_id] ?? NULL) === $selected_value);
            if ($value_row && isset($value_row[$format])) {
              $output[] = $value_row[$format];
            }
          }

          if ($output) {
            return $output;
          }
        }
      }
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $field_values = $this->getFieldValuesFromFormat($element, $webform_submission, $options);
    if ($field_values !== null) {
      $field_values = array_values(array_unique($field_values));
      if (count($field_values) === 1) {
        return ['#plain_text' => $field_values[0]];
      }

      return [
        '#theme' => 'item_list',
        '#items' => $field_values,
      ];
    }

    return parent::formatHtmlItem($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $field_values = $this->getFieldValuesFromFormat($element, $webform_submission, $options);
    if ($field_values !== null) {
      return ['#plain_text' => implode(', ', array_values(array_unique($field_values)))];
    }

    return parent::formatTextItem($element, $webform_submission, $options);
  }

  public static function setOptions(array &$element, array $settings = []) {
    if (!empty($element['#options'])) {
      return;
    }

    $field_data = self::renderViewFields($element);
    $label_field_id = $element['#label_field'] ?? '';
    $value_field_id = $element['#value_field'] ?? '';
    $options = [];
    foreach ($field_data as $row) {
      $options[$row[$value_field_id]] = $row[$label_field_id];
    }

    $options = WebformOptionsHelper::stripTagsOptions($options);
    $options = WebformOptionsHelper::decodeOptions($options);

    $element['#options'] = $options;
  }
}
