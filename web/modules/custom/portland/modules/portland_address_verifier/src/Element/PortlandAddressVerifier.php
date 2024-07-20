<?php

namespace Drupal\portland_address_verifier\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Entity\WebformOptions;


/**
 * Provides a 'portland_address_verifier'.
 *
 * Portland Address Verifier widget is comprised of a group of sub-elements and
 * client-side scripting to validate addresses in the Portland area using the 
 * PortlandMaps API.
 * 
 * This widget is ONLY concerned about addresses. It has no zero support for
 * lat/lon coordinates. However, location data returned from the API, such as
 * loacation types, tax lot ID, municipality name, and zipcode, are captured.
 *
 * IMPORTANT:
 * 1. This widget cannot be used for geofencing; the full Location Picker widget must be used 
 *    for anything other than simple address verification the areas covered by Portland Maps
 * 2. Webform composite can not contain multiple value elements (i.e. checkboxes)
 *    or composites (i.e. webform_address)
 *
 * @FormElement("portland_address_verifier")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\portland_address_verifier\Element\PortlandAddressVerifier
 */
class PortlandAddressVerifier extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   * //NOTE: custom elements must have a #title attribute. if a value is not set here, it must be set
   * //in the field config. if not, an error is thrown when trying to add an email handler.
   * 
   * How to programmatically set field conditions: https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
   */
  public static function getCompositeElements(array $element) {

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'park_facility', 'status' => 1]);

    // $element_id = "report_location";
    
    // if (array_key_exists("#webform_key", $element)) {
    //   $element_id = $element['#webform_key'];
    // }

    $state_codes = WebformOptions::load('state_codes')->getOptions();

    $element['location_address'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
      '#id' => 'location_address',
      '#attributes' => ['autocomplete' => 'off'],
      '#description' => t('Begin typing to see a list of possible address matches in the Portland metro area. Do not incude unit number.'),
      '#description_display' => 'before',
      '#required_error' => 'Please enter an address and verify it.',
      '#required' => TRUE,
      '#more_title' => 'More info',
      '#more' => '<p><em>Address data is provided by <a href="https://portlandmaps.com" target="_blank">PortlandMaps.com</a>. We can only verify addresses in the Portland metro area that are included in the PortlandMaps.com database. We\'re currently unable to verify unit numbers and P.O. boxes, but you may still submit your address if you\'re certain it\'s correct.</em></p>',
    ];
    $element['location_address_street_number'] = [
      '#type' => 'textfield',
      '#title' => t('Street Number'),
      '#attributes' => ['id' => 'location_address_street_number']
    ];
    $element['location_address_street_quadrant'] = [
      '#type' => 'textfield',
      '#title' => t('Street Quadrant'),
      '#attributes' => ['id' => 'location_address_street_quadrant']
    ];
    $element['location_address_street_name'] = [
      '#type' => 'textfield',
      '#title' => t('Street Name'),
      '#attributes' => ['id' => 'location_address_street_name']
    ];
    $element['container_unit'] = [
      '#type' => 'container',
      '#id' => 'container_unit',
      '#title' => 'container_unit' // undefined array key #title error thrown if no title on this container (?!)
    ];
    $element['container_unit']['has_unit'] = [
      '#type' => 'checkbox',
      '#title' => t('This address has a unit number (apartment, suite, floor, unit, etc.)'),
      '#id' => 'has_unit',
    ];
    $element['container_unit']['unit_number'] = [
      '#type' => 'textfield',
      '#title' => t('Unit Number'),
      '#id' => 'unit_number',
      '#attributes' => ['autocomplete' => 'off'],
      '#placeholder' => t('e.g. #101, APT 101, or UNIT 101'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[id="has_unit"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['location_city'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#id' => 'location_city',
      '#wrapper_attributes' => [
        'class' => ['webform-city'],
      ],
      '#required' => TRUE,
    ];
    $element['location_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => $state_codes,
      '#default_value' => 'OR',
      '#id' => 'location_state',
      '#wrapper_attributes' => ['class' => ['webform-state']],
      '#required' => TRUE,
    ];
    $element['location_zip'] = [
      '#type' => 'textfield',
      '#title' => t('ZIP Code'),
      '#id' => 'location_zip',
      '#attributes' => ['class' => ['webform-zip']],
      '#wrapper_attributes' => ['class' => ['webform-zip']],
      '#required' => TRUE,
    ];
    $element['location_address_label_markup'] = [
      '#type' => 'markup',
      '#title' => t('Mailing label example'),
      '#title_display' => 'invisible',
      '#markup' => '<div id="location_address_label_markup" class="mailing-label d-none"><p><em>This is how the address would appear on a mailing label:</em></p><div id="mailing_label"></div></div>',
    ];
    $element['suggestions_modal'] = [
      '#type' => 'markup',
      '#title' => 'Suggestions',
      '#title_display' => 'invisible',
      '#markup' => '<div id="suggestions_modal" class="visually-hidden"></div>',
    ];
    $element['status_modal'] = [
      '#type' => 'markup',
      '#title' => 'Status Indicator',
      '#title_display' => 'invisible',
      '#markup' => '<div id="status_modal" class="visually-hidden"></div>',
    ];
    $element['not_found_modal'] = [
      '#type' => 'markup',
      '#title' => 'Address Not Found',
      '#title_display' => 'invisible',
      '#markup' => '<div id="not_found_modal" class="visually-hidden"></div>',
    ];
    // $element['verify_button'] = [
    //   '#type' => 'button',
    //   '#value' => t('Verify Address'),
    //   '#attributes' => [
    //     'class' => ['button', 'button--primary', 'js-form-submit', 'form-submit', 'btn-verify'],
    //   ],
    // ];
    // $element['location_street'] = [
    //   '#type' => 'textfield',
    //   '#title' => t('Street'),
    //   '#attributes' => ['id' => 'location_street']
    // ];
    // $element['location_city'] = [
    //   '#type' => 'textfield',
    //   '#title' => t('City'),
    //   '#attributes' => ['id' => 'location_city']
    // ];
    // $element['location_state'] = [
    //   '#type' => 'textfield',
    //   '#title' => t('State'),
    //   '#attributes' => ['id' => 'location_state']
    // ];
    // $element['location_zip'] = [
    //   '#type' => 'textfield',
    //   '#title' => t('Zip'),
    //   '#attributes' => [ 'id' => 'location_zip']
    // ];
    $element['location_lat'] = [
      '#type' => 'textfield',
      '#title' => t('Latitude'),
      '#attributes' => [ 'id' => 'location_lat']
    ];
    $element['location_lon'] = [
      '#type' => 'textfield',
      '#title' => t('Longitude'),
      '#attributes' => [ 'id' => 'location_lon']
    ];
    $element['location_x'] = [
      '#type' => 'textfield',
      '#title' => t('Coordinates X'),
      '#attributes' => [ 'id' => 'location_x']
    ];
    $element['location_y'] = [
      '#type' => 'textfield',
      '#title' => t('Coordinates Y'),
      '#attributes' => [ 'id' => 'location_y']
    ];
    $element['location_taxlot_id'] = [
      '#type' => 'textfield',
      '#title' => t('Taxlot ID'),
      '#attributes' => [ 'id' => 'location_taxlot_id']
    ];
    $element['location_is_unincorporated'] = [
      '#type' => 'textfield',
      '#title' => t('Is Unincorporated'),
      '#attributes' => [ 'id' => 'location_is_unincorporated']
    ];
    $element['location_address_label'] = [
      '#type' => 'textfield',
      '#title' => t('Address Label'),
      '#attributes' => [ 'id' => 'location_address_label']
    ];
    $element['location_verification_status'] = [
      '#type' => 'textfield',
      '#title' => t('Verification Status'),
      '#attributes' => [ 'id' => 'location_verification_status'],
      '#required_error' => 'Please verify the address before continuing.'
    ];
    $element['location_data'] = [
      '#type' => 'textfield',
      '#title' => t('Location Data'),
      '#attributes' => [ 'id' => 'location_data']
    ];

    return $element;
  }
}
