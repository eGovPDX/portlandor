<?php

namespace Drupal\portland_address_verifier\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

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

    $element['location_address'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
      '#id' => 'location_address',
      '#attributes' => ['autocomplete' => 'off'],
      '#description' => t('Begin typing to see a list of possible address matches. Do not incude unit number.'),
      '#description_display' => 'before',
      // '#required' => TRUE,
      '#required_error' => 'Please enter an address and verify it.'
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
      // '#description' => t('If there is an apartment, suite, floor or other unit number, enter it here exactly as it should appear in your address.'),
      // '#description_display' => 'before',
      '#placeholder' => t('e.g. #101, APT 101, or UNIT 101'),
      '#states' => [
        'visible' => [
          ':input[id="has_unit"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element['location_address_label_markup'] = [
      '#type' => 'markup',
      '#title' => t('Mailing label example'),
      '#title_display' => 'invisible',
      // '#help' => t('This is how the address would appear on a mailing label.'),
      // '#help_display' => 'title_after',
      '#markup' => '<div id="location_address_label_markup" class="mailing-label d-none"><p><em>This is how the address would appear on a mailing label:</em></p><div id="mailing_label"></div></div>',
      // '#states' => [
      //   'hidden' => [
      //     ':input[id="location_address"]' => ['filled' => FALSE],
      //   ],
      // ],
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
    $element['location_street'] = [
      '#type' => 'hidden',
      '#title' => t('Street'),
      '#attributes' => ['id' => 'location_street']
    ];
    $element['location_street_number'] = [
      '#type' => 'hidden',
      '#title' => t('Street Number'),
      '#attributes' => ['id' => 'location_street_number']
    ];
    $element['location_street_quadrant'] = [
      '#type' => 'hidden',
      '#title' => t('Street Quadrant'),
      '#attributes' => ['id' => 'location_street_quadrant']
    ];
    $element['location_street_name'] = [
      '#type' => 'hidden',
      '#title' => t('Street Name'),
      '#attributes' => ['id' => 'location_street_name']
    ];
    $element['location_city'] = [
      '#type' => 'hidden',
      '#title' => t('City'),
      '#attributes' => ['id' => 'location_city']
    ];
    $element['location_state'] = [
      '#type' => 'hidden',
      '#title' => t('State'),
      '#attributes' => ['id' => 'location_state']
    ];
    $element['location_zip'] = [
      '#type' => 'hidden',
      '#title' => t('Zip'),
      '#attributes' => [ 'id' => 'location_zip']
    ];
    $element['location_lat'] = [
      '#type' => 'hidden',
      '#title' => t('Latitude'),
      '#attributes' => [ 'id' => 'location_lat']
    ];
    $element['location_lon'] = [
      '#type' => 'hidden',
      '#title' => t('Longitude'),
      '#attributes' => [ 'id' => 'location_lon']
    ];
    $element['location_x'] = [
      '#type' => 'hidden',
      '#title' => t('Coordinates X'),
      '#attributes' => [ 'id' => 'location_x']
    ];
    $element['location_y'] = [
      '#type' => 'hidden',
      '#title' => t('Coordinates Y'),
      '#attributes' => [ 'id' => 'location_y']
    ];
    $element['location_address_label'] = [
      '#type' => 'hidden',
      '#title' => t('Address Label'),
      '#attributes' => [ 'id' => 'location_address_label']
    ];
    $element['location_verification_status'] = [
      '#type' => 'hidden',
      '#title' => t('Verification Status'),
      '#attributes' => [ 'id' => 'location_verification_status'],
      // '#required' => TRUE,
      '#required_error' => 'Please verify the address before continuing.'
    ];
    $element['location_data'] = [
      '#type' => 'hidden',
      '#title' => t('Location Data'),
      '#attributes' => [ 'id' => 'location_data']
    ];

    return $element;
  }
}
