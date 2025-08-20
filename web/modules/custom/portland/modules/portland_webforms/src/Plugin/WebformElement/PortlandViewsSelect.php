<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\webform\Element\WebformAjaxElementTrait;
use Drupal\webform\Plugin\WebformElement\Select;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'portland_views_select' element.
 *
 * @WebformElement(
 *   id = "portland_views_select",
 *   label = @Translation("Portland Views Select"),
 *   description = @Translation("A select element populated from a View display."),
 *   category = @Translation("Options elements")
 * )
 */
class PortlandViewsSelect extends Select {
  use PortlandViewsSelectTrait;
  use WebformAjaxElementTrait;

  /**
   * {@inheritdoc}
   */
  public function defineDefaultProperties() {
    $properties = [
      'view_id' => '',
      'display_id' => '',
      'label_field' => '',
      'value_field' => '',
      'cache_ttl_sec' => 20,
    ] + parent::defineDefaultProperties();
    unset(
      $properties['options'],
      $properties['options_description_display']
    );

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $this->setOptions($element, ['webform_submission' => $webform_submission]);
    parent::prepare($element, $webform_submission);
  }

  public static function getViewDisplays(string $view_id) {
    $view_storage = \Drupal::entityTypeManager()->getStorage('view');
    $view = $view_storage->load($view_id);
    if ($view) {
      $displays = $view->get('display');
      return array_combine(array_keys($displays), array_column($displays, 'display_title'));
    }

    return [];
  }

  public static function getViewFields(string $view_id, string $display_id) {
    $view = Views::getView($view_id);
    $options = [];
    if ($view && $view->access($display_id)) {
      $view->build($display_id);

      foreach ($view->field as $id => $field) {
        $options[$id] = "{$field->adminLabel()} ({$id})";
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $element_properties = $form_state->get('element_properties');
    // If form is rebuilding from AJAX, get values from user input because
    // $form_state->get() is blank.
    if ($form_state->isRebuilding()) {
      $element_properties = $form_state->getUserInput()['properties'] ?? [];
    }

    $form['views_select'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Views select settings'),
      '#weight' => -40,
    ];

    $form['views_select']['view_id'] = [
      '#type' => 'select2',
      '#title' => t('View'),
      '#default_value' => $element_properties['view_id'] ?? '',
      '#required' => TRUE,
      '#description' => t('The view to use.'),
      '#options' => Views::getViewsAsOptions(true, 'enabled'),
    ];

    // create container for ajax trigger
    $form['views_select']['view_settings'] = [];
    $form['views_select']['view_settings']['display_id'] = [
      '#type' => 'select',
      '#title' => t('View display ID'),
      '#default_value' => $element_properties['display_id'] ?? '',
      '#required' => TRUE,
      '#description' => t('The display ID of the view to use. Display must be using the <strong>Fields</strong> style plugin.'),
      '#options' => self::getViewDisplays($element_properties['view_id']),
    ];
    $this->buildAjaxElement('views-select-view-settings', $form['views_select']['view_settings'], $form['views_select']['view_id']);

    $view_fields = self::getViewFields($element_properties['view_id'], $element_properties['display_id']);
    // create container for ajax trigger
    $form['views_select']['view_settings']['display_settings'] = [];
    $form['views_select']['view_settings']['display_settings']['label_field'] = [
      '#type' => 'select',
      '#title' => t('Label field'),
      '#default_value' => $element_properties['label_field'] ?? '',
      '#required' => TRUE,
      '#description' => t('The field from the view result to use as the option label.'),
      '#options' => $view_fields,

    ];
    $form['views_select']['view_settings']['display_settings']['value_field'] = [
      '#type' => 'select',
      '#title' => t('Value field'),
      '#default_value' => $element_properties['value_field'] ?? '',
      '#required' => TRUE,
      '#description' => t('The field from the view result to use as the option value.'),
      '#options' => $view_fields,
    ];
    $this->buildAjaxElement('views-select-display-settings', $form['views_select']['view_settings']['display_settings'], $form['views_select']['view_settings']['display_id']);

    $form['views_select']['cache_ttl_sec'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => t('Cache TTL (seconds)'),
      '#default_value' => $element_properties['cache_ttl_sec'] ?? 20,
      '#required' => TRUE,
      '#description' => t('The time to cache view data, in seconds. This is useful if you want to cache the view for a shorter period than built-in views caching allows (e.g. for live inventory counts).'),
    ];

    return $form;
  }
}
