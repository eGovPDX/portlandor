<?php

namespace Drupal\portland_webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for searching and verifying street addresses in the City of Portland and surrounding areas.
 *
 *
 * @FormElement("portland_webform_address_verifier")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\portland_webform\Element\PortlandLocationPicker
 */
class PortlandWebformAddressVerifier extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   * NOTE: custom elements must have a #title attribute. if a value is not set here, it must be set
   *       in the field config. if not, an error is thrown when trying to add an email handler.
   * 
    * How to programmatically set field conditions: https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
   */
  public static function getCompositeElements(array $element) {

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'park_facility', 'status' => 1]);

    $element_id = "report_location";
    
    if (array_key_exists("#webform_key", $element)) {
      $element_id = $element['#webform_key'];
    }

    $element['address_singleline'] = [
      '#type' => 'textfield',
      '#title' => t('Address search'),
      '#id' => 'address_singleline',
      '#attributes' => ['class' => ['location-picker-address'], 'autocomplete' => 'off'],
      '#description' => t('Enter an address to verify.'),
      '#description_display' => 'before',
    ];
    // Unit
    // Unit type
    $element['address_suggestions_modal'] = [
      '#type' => 'markup',
      '#title' => 'Address suggestions modal',
      '#title_display' => 'invisible',
      '#markup' => '<div id="address_suggestions_modal" class="visually-hidden"></div>',
    ];
    $element['address_status_modal'] = [
      '#type' => 'markup',
      '#title' => 'Address status indicator',
      '#title_display' => 'invisible',
      '#markup' => '<div id="address_status_modal" class="visually-hidden"></div>',
    ];
    
    $element['address_street'] = [
      '#type' => 'textfield',
      '#title' => t('Street'),
      '#attributes' => ['id' => 'location_asset_id'],
    ];
    $element['address_street_line2'] = [
      '#type' => 'textfield',
      '#title' => t('Company name, C/O, etc.'),
      '#attributes' => ['id' => 'address_street_line2'],
    ];
    $element['address_city'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#attributes' => ['id' => 'address_city'],
    ];
    $element['address_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => 'state_codes',
      '#default_value' => 'OR',
      '#attributes' => ['id' => 'address_state'],
    ];
    $element['address_zip'] = [
      '#type' => 'textfield',
      '#title' => t('Zipcode'),
      '#attributes' => ['id' => 'address_zip'],
    ];

    return $element;
  }
}
