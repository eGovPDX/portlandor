<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\views\Views;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformSubmissionInterface;

trait PortlandViewsSelectTrait {
  private const CACHE_PREFIX = 'portland_webforms:views_select:';

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
      } else {
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
   * Handle when the format is a field from a view, e.g. `webform_submission:values:my_select:cell_2`.
   * If the format is not a recognized field from the view, this function will return null and the caller should fallback to normal handling.
   */
  protected function getFieldValueFromFormat(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
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
          // we're currently making the assumption that there aren't any duplicate values
          $value_row = array_find($field_data, fn($row) => $row[$value_field_id] === $value);
          if ($value_row) {
            return [ '#plain_text' => $value_row[$format] ];
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
    $field_value = $this->getFieldValueFromFormat($element, $webform_submission, $options);
    if ($field_value) return $field_value;

    return parent::formatHtmlItem($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $field_value = $this->getFieldValueFromFormat($element, $webform_submission, $options);
    if ($field_value) return $field_value;

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
