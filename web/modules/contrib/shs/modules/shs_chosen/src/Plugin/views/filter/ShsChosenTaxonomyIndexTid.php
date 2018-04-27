<?php

/**
 * @file
 * Contains \Drupal\shs_chosen\Plugin\views\filter\ShsChosenTaxonomyIndexTid.
 */

namespace Drupal\shs_chosen\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\shs\Plugin\views\filter\ShsTaxonomyIndexTid;

/**
 * Filter by term id using "Simple hierarchical select: chosen" widgets.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("shs_chosen_taxonomy_index_tid")
 */
class ShsChosenTaxonomyIndexTid extends ShsTaxonomyIndexTid {

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildExtraOptionsForm($form, $form_state);

    $form['type']['#options']['shs_chosen'] = $this->t('Simple hierarchical select: Chosen');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['expose']['contains']['chosen_override'] = ['default' => FALSE];
    $options['expose']['contains']['disable_search'] = ['default' => FALSE];
    $options['expose']['contains']['search_contains'] = ['default' => FALSE];
    $options['expose']['contains']['placeholder_text_multiple'] = ['default' => ''];
    $options['expose']['contains']['placeholder_text_single'] = ['default' => ''];
    $options['expose']['contains']['no_results_text'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['chosen_override'] = FALSE;
    $this->options['expose'] = $this->defaultChosenSettings() + $this->options['expose'];
  }

  /**
   * Get the default settings for chosen.
   *
   * @return array
   *   List of settings.
   */
  protected function defaultChosenSettings() {
    return [
      'disable_search' => FALSE,
      'search_contains' => FALSE,
      'placeholder_text_multiple' => $this->t('Choose some options'),
      'placeholder_text_single' => $this->t('Choose an option'),
      'no_results_text' => $this->t('No results match'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['chosen_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Custom chosen settings'),
      '#default_value' => !empty($this->options['expose']['chosen_override']),
      '#description' => $this->t('Override !settings made for chosen.', ['!settings' => Link::createFromRoute('global settings', 'chosen.admin')->toString()]),
    ];

    $chosen_settings = $this->options['expose'] + $this->defaultChosenSettings();
    $form['fieldsets']['#value'][] = 'chosen_settings';
    $form['expose']['chosen_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Chosen overrides'),
      '#open' => TRUE,
      '#states' => [
        'invisible' => [
          'input[name="options[expose][chosen_override]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['expose']['chosen_settings']['disable_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable search box'),
      '#default_value' => $chosen_settings['disable_search'],
      '#parents' => ['options', 'expose', 'disable_search'],
    ];
    $form['expose']['chosen_settings']['search_contains'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Search also in the middle of words'),
      '#default_value' => $chosen_settings['search_contains'],
      '#parents' => ['options', 'expose', 'search_contains'],
    ];
    $form['expose']['chosen_settings']['placeholder_text_multiple'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder text of multiple selects'),
      '#default_value' => $chosen_settings['placeholder_text_multiple'],
      '#parents' => ['options', 'expose', 'placeholder_text_multiple'],
    ];
    $form['expose']['chosen_settings']['placeholder_text_single'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder text of single selects'),
      '#default_value' => $chosen_settings['placeholder_text_single'],
      '#parents' => ['options', 'expose', 'placeholder_text_single'],
    ];
    $form['expose']['chosen_settings']['no_results_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No results text'),
      '#default_value' => $chosen_settings['no_results_text'],
      '#parents' => ['options', 'expose', 'no_results_text'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $type = $this->options['type'];
    if ('shs_chosen' !== $type) {
      // Let the parent class generate the base form.
      parent::valueForm($form, $form_state);
      return;
    }
    // Small hack to process the element as shs element.
    $this->options['type'] = 'shs';
    parent::valueForm($form, $form_state);
    // Reset type.
    $this->options['type'] = $type;
    if (empty($this->options['expose']['chosen_override'])) {
      // No need to modify the display of the form element.
      return;
    }

    if (empty($form['value']['#shs'])) {
      // Something went wrong.
      return;
    }

    $chosen_defaults = $this->defaultChosenSettings();
    // Get overridden chosen settings.
    $chosen_overrides = array_intersect_key($this->options['expose'], $chosen_defaults);
    $chosen_conf = $chosen_overrides + \Drupal::config('chosen.settings')->get();

    $settings_shs = $form['value']['#shs'];
    $settings_shs['chosen_override'] = TRUE;
    $settings_shs['chosen_settings'] = $chosen_conf;
    if (empty($settings_shs['display'])) {
      $settings_shs['display'] = [];
    }

    $settings_shs['display']['chosen'] = [
      'selector' => 'select.shs-select',
      'minimum_single' => 0,
      'minimum_multiple' => 0,
      'minimum_width' => (int) $chosen_conf['minimum_width'],
      'options' => [
        'disable_search' => (bool) $chosen_conf['disable_search'],
        'disable_search_threshold' => (int) $chosen_conf['disable_search_threshold'],
        'search_contains' => (bool) $chosen_conf['search_contains'],
        'placeholder_text_multiple' => $chosen_conf['placeholder_text_multiple'],
        'placeholder_text_single' => $chosen_conf['placeholder_text_single'],
        'no_results_text' => $chosen_conf['no_results_text'],
        'inherit_select_classes' => TRUE,
      ],
    ];

    $context = [
      'settings' => $settings_shs,
      'object' => $this,
    ];
    $settings_shs['classes'] = shs_get_class_definitions($this->definition['field_name'], $context);

    $form['value']['#attached']['drupalSettings']['shs'][$form['value']['#attributes']['data-shs-selector']] = $form['value']['#shs'] = $settings_shs;
    $form['value']['#attached']['library'][] = 'shs_chosen/shs_chosen.form';
  }

}
