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

    $state_codes = WebformOptions::load('state_codes')->getOptions();

    $element['location_address'] = [
      '#type' => 'textfield',
      '#title' => t('Street Address'),
      '#id' => 'location_address',
      '#attributes' => ['autocomplete' => 'off'],
      '#description' => t('Begin typing to see a list of possible address matches in the Portland metro area, then select one. Do not include unit number.'),
      '#description_display' => 'before',
      '#required_error' => 'Please enter an address and verify it.',
    ];
    $element['location_full_address'] = [
      '#type' => 'hidden',
      '#title' => t('Full Address'),
      '#attributes' => ['id' => 'location_full_address']
    ];
    $element['location_address_street_number'] = [
      '#type' => 'hidden',
      '#title' => t('Street Number'),
      '#attributes' => ['id' => 'location_address_street_number']
    ];
    $element['location_address_street_quadrant'] = [
      '#type' => 'hidden',
      '#title' => t('Street Quadrant'),
      '#attributes' => ['id' => 'location_address_street_quadrant']
    ];
    $element['location_address_street_name'] = [
      '#type' => 'hidden',
      '#title' => t('Street Name'),
      '#attributes' => ['id' => 'location_address_street_name']
    ];
    $element['location_address_street_type'] = [
      '#type' => 'hidden',
      '#title' => t('Street Type'),
      '#attributes' => ['id' => 'location_address_street_type']
    ];
    $element['unit_number'] = [
      '#type' => 'textfield',
      '#title' => t('Unit Number'),
      '#id' => 'unit_number',
      '#attributes' => ['autocomplete' => 'off'],
      '#placeholder' => t('e.g. #101, APT 101, or UNIT 101'),
      '#wrapper_attributes' => [
        'class' => ['mb-0'],
      ],
    ];
    $element['location_city'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#id' => 'location_city',
      '#wrapper_attributes' => [
        'class' => ['webform-city', 'mb-0'],
      ],
    ];
    $element['location_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => $state_codes,
      '#default_value' => 'OR',
      '#id' => 'location_state',
      '#wrapper_attributes' => ['class' => ['webform-state', 'mb-0']],
    ];
    $element['location_zip'] = [
      '#type' => 'textfield',
      '#title' => t('ZIP Code'),
      '#id' => 'location_zip',
      '#attributes' => ['class' => ['webform-zip']],
      '#wrapper_attributes' => ['class' => ['webform-zip', 'mb-0']],
    ];
    $element['location_jurisdiction'] = [
      '#type' => 'hidden',
      '#title' => t('Jurisdiction'),
      '#id' => 'location_jurisdiction',
    ];
    $element['av_suggestions_modal'] = [
      '#type' => 'markup',
      '#title' => 'Suggestions',
      '#title_display' => 'invisible',
      '#markup' => '<div id="av_suggestions_modal" class="visually-hidden"></div>',
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
    $element['location_taxlot_id'] = [
      '#type' => 'hidden',
      '#title' => t('Taxlot ID'),
      '#attributes' => [ 'id' => 'location_taxlot_id']
    ];
    $element['location_is_unincorporated'] = [
      '#type' => 'hidden',
      '#title' => t('Is Unincorporated'),
      '#attributes' => [ 'id' => 'location_is_unincorporated']
    ];
    $element['location_address_label'] = [
      '#type' => 'hidden',
      '#title' => t('Address Label'),
      '#attributes' => [ 'id' => 'location_address_label']
    ];
    $element['location_verification_status'] = [
      '#type' => 'textfield',
      '#title' => t('Address Verification'),
      '#attributes' => [ 'id' => 'location_verification_status', 'class' => ['visually-hiddenx']],
      '#required_error' => 'The address is not verified.',
      '#title_display' => 'invisible',
      '#wrapper_attributes' => [
        'class' => ['mt-0', 'mb-0'],
      ],
    ];
    $element['location_capture_field'] = [
      '#type' => 'hidden',
      '#title' => t('Capture Field'),
      '#attributes' => [ 'id' => 'location_capture_field'],
    ];
    $element['location_data'] = [
      '#type' => 'hidden',
      '#title' => t('Location Data'),
      '#attributes' => [ 'id' => 'location_data']
    ];

    return $element;
  }
}
