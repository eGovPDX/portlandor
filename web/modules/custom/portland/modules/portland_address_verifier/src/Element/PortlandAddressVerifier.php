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
      '#title' => t('Location Search'),
      '#id' => 'location_address',
      '#attributes' => ['autocomplete' => 'off'],
      '#description' => t('Enter an address to verify.'),
      '#description_display' => 'before',
    ];
    $element['unit_number'] = [
      '#type' => 'textfield',
      '#title' => t('Unit Number'),
      '#id' => 'location_address',
      '#attributes' => ['autocomplete' => 'off'],
      '#placeholder' => t('e.g. 101, APT 101, or UNIT 101'),
    ];
    $element['suggestions_modal'] = [
      '#type' => 'markup',
      '#title' => 'Suggestions',
      '#title_display' => 'invisible',
      '#markup' => '<div id="suggestions_modal" class="visually-hidden"></div>',
    ];
    $element['status_modal'] = [
      '#type' => 'markup',
      '#title' => 'Status indicator',
      '#title_display' => 'invisible',
      '#markup' => '<div id="status_modal" class="visually-hidden"></div>',
    ];
    $element['location_street'] = [
      '#type' => 'textfield',
      '#title' => t('Street'),
      '#attributes' => ['id' => 'location_street']
    ];
    $element['location_city'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#attributes' => ['id' => 'location_city']
    ];
    $element['location_state'] = [
      '#type' => 'textfield',
      '#title' => t('State'),
      '#attributes' => ['id' => 'location_state']
    ];
    $element['location_zip'] = [
      '#type' => 'textfield',
      '#title' => t('Zip'),
      '#attributes' => [ 'id' => 'location_zip']
    ];
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

    return $element;
  }
}
