<?php

/**
 * @file
 * Contains \Drupal\shs_chosen\Plugin\Field\FieldWidget\OptionsShsChosenWidget.
 */

namespace Drupal\shs_chosen\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\shs\Plugin\Field\FieldWidget\OptionsShsWidget;

/**
 * Plugin implementation of the 'options_shs_chosen' widget.
 *
 * @FieldWidget(
 *   id = "options_shs_chosen",
 *   label = @Translation("Simple hierarchical select: Chosen"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class OptionsShsChosenWidget extends OptionsShsWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings_default = [
      'chosen_override' => FALSE,
      'chosen_settings' => [
        'disable_search' => FALSE,
        'search_contains' => FALSE,
        'placeholder_text_multiple' => t('Choose some options'),
        'placeholder_text_single' => t('Choose an option'),
        'no_results_text' => t('No results match'),
      ],
    ];
    return $settings_default + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $element = parent::settingsForm($form, $form_state);

    // Add custom settings.
    $element['chosen_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Custom chosen settings'),
      '#default_value' => $this->getSetting('chosen_override'),
      '#description' => $this->t('Override <a href=":url">global settings</a> made for chosen.', [':url' => Url::fromRoute('chosen.admin')->toString()]),
    ];

    $chosen_settings = $this->getSetting('chosen_settings');
    $element['chosen_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Chosen overrides'),
      '#open' => TRUE,
      '#states' => [
        'invisible' => [
          'input[name="fields[' . $field_name . '][settings_edit_form][settings][chosen_override]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $element['chosen_settings']['disable_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable search box'),
      '#default_value' => $chosen_settings['disable_search'],
    ];
    $element['chosen_settings']['search_contains'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Search also in the middle of words'),
      '#default_value' => $chosen_settings['search_contains'],
    ];
    $element['chosen_settings']['placeholder_text_multiple'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder text of multiple selects'),
      '#default_value' => $chosen_settings['placeholder_text_multiple'],
    ];
    $element['chosen_settings']['placeholder_text_single'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder text of single selects'),
      '#default_value' => $chosen_settings['placeholder_text_single'],
    ];
    $element['chosen_settings']['no_results_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No results text'),
      '#default_value' => $chosen_settings['no_results_text'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('chosen_override')) {
      $summary[] = $this->t('Override chosen settings');
    }
    else {
      $summary[] = $this->t('Use <a href=":url">global chosen settings</a>', [':url' => Url::fromRoute('chosen.admin')->toString()]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#attached']['library'][] = 'shs_chosen/shs_chosen.form';

    return $element;
  }

}
