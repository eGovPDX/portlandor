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

    $element_id = "report_location";
    
    if (array_key_exists("#webform_key", $element)) {
      $element_id = $element['#webform_key'];
    }

    $element['location_search'] = [
      '#type' => 'textfield',
      '#title' => t('Location Search'),
      '#id' => 'location_search',
      '#attributes' => ['class' => ['location-picker-address'], 'autocomplete' => 'off'],
      '#description' => t('Search the map for an address, cross streets, park, or community center. Or use the map to click a location.'),
      '#description_display' => 'before',
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
    $element['location_address'] = [
      '#type' => 'hidden',
      '#title' => t('Location Address'),
      '#attributes' => ['class' => ['location-picker-address'], 'autocomplete' => 'off', 'id' => 'location_address']
    ];
    $element['location_types'] = [
      '#type' => 'hidden',
      '#title' => t('Location types'),
      '#attributes' => ['id' => 'location_types'],
    ];
    $element['location_type_taxlot'] = [
      '#type' => 'hidden',
      '#title' => 'Taxlot',
      '#attributes' => ['id' => 'location_type_taxlot'],
    ];
    $element['location_type_park'] = [
      '#type' => 'hidden',
      '#title' => 'Park',
      '#attributes' => ['id' => 'location_type_park'],
    ];
    $element['location_type_waterbody'] = [
      '#type' => 'hidden',
      '#title' => 'Waterbody',
      '#attributes' => ['id' => 'location_type_waterbody'],
    ];
    $element['location_type_trail'] = [
      '#type' => 'hidden',
      '#title' => 'Trail',
      '#attributes' => ['id' => 'location_type_trail'],
    ];
    $element['location_type_stream'] = [
      '#type' => 'hidden',
      '#title' => 'Stream',
      '#attributes' => ['id' => 'location_type_stream'],
    ];
    $element['location_type_street'] = [
      '#type' => 'hidden',
      '#title' => 'Street',
      '#attributes' => ['id' => 'location_type_street'],
    ];
    $element['location_type_row'] = [
      '#type' => 'hidden',
      '#title' => 'ROW',
      '#attributes' => ['id' => 'location_type_row'],
    ];

    $element['location_municipality_name'] = [
      '#type' => 'hidden',
      '#title' => t('Municipality Name'),
      '#attributes' => ['class' => ['location-municipality-name'], 'id' => 'location_municipality_name'],
    ];
    $element['location_zipcode'] = [
      '#type' => 'hidden',
      '#title' => t('Zipcode'),
      '#attributes' => [ 'id' => 'location_zipcode']
    ];

    return $element;
  }
}
