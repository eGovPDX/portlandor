<?php

namespace Drupal\portland_webforms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'portland_location_widget'.
 *
 * Webform composites contain a group of sub-elements.
 *
 * @FormElement("portland_location_widget")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\portland_webforms\Element\PortlandLocationWidget
 */
class PortlandLocationWidget extends WebformCompositeBase
{
  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element)
  {
    $element_id = array_key_exists("#webform_key", $element) ? $element['#webform_key'] : "";
    
    $element['location_address'] = [
      '#type' => 'textfield',
      '#title' => t('Location Address'),
      '#id' => $element_id . '_location_search',
      '#attributes' => ['class' => ['location-widget-address'], 'autocomplete' => 'off'],
      '#description' => t('Search the map for an address, cross streets, park, or community center. Or use the map to click a location.'),
      '#description_display' => 'before',
    ];
    $element['precision_text'] = [
      '#type' => 'markup',
      '#title' => 'Precision',
      '#title_display' => 'invisible',
      '#markup' => '<div class="alert alert--info next-steps visually-hidden precision_text" aria-hidden="true" id="precision_text"><strong>IMPORTANT:</strong> To help us provide better service, please click, tap, or drag the marker to the precise location on the map.</div>',
    ];
    $element['location_map'] = [
      '#type' => 'markup',
      '#id' => 'location_map',
      '#title' => 'Location map',
      '#description' => '',
      '#description_display' => 'before',
      '#title_display' => 'invisible',
      '#markup' => '<div id="' . $element_id . '" class="location-map" tabindex="0"></div><div class="loader-container" role="status"><div class="loader"></div><div class="visually-hidden">' . t('Loading...') . '</div></div>',
    ];

    // hidden fields to store data about the location
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
      '#type' => 'hidden',
      '#title' => t('Verification Status'),
      '#attributes' => [ 'id' => 'location_verification_status'],
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
