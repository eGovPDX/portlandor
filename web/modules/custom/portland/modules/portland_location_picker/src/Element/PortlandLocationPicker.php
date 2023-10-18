<?php

namespace Drupal\portland_location_picker\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'portland_location_picker'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("portland_location_picker")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\portland_location_picker\Element\PortlandLocationPicker
 */
class PortlandLocationPicker extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   * //NOTE: custom elements must have a #title attribute. if a value is not set here, it must be set
   * //in the field config. if not, an error is thrown when trying to add an email handler.
   * 
   * Location types:
   *  street - location_address, location_map
   *  park - location_park, location_map
   *  waterway - location_map
   *  private - location_private_owner
   *  other - location_address, location_map
   * 
   * How to programmatically set field conditions: https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
   */
  public static function getCompositeElements(array $element) {

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'park_facility', 'status' => 1]);

    $element_id = "report_location";
    
    if (array_key_exists("#webform_key", $element)) {
      $element_id = $element['#webform_key'];
    }

    $element['location_address'] = [
      '#type' => 'textfield',
      '#title' => t('Location Search'),
      '#id' => 'location_address',
      '#attributes' => ['class' => ['location-picker-address'], 'autocomplete' => 'off'],
      '#description' => t('Search by address, cross streets, park name, or community center. Or use the Locate Me button or click/tap the map to select a precise location.'),
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
      '#markup' => '<div id="location_map_container" class="location-map"></div>',
    ];
    $element['location_type'] = [
      '#id' => 'location_type',
      '#name' => 'location_type',
      '#type' => 'radios',
      '#title' => t('What type of location is this?'),
      '#title_display' => 'before',
      '#options' => [
        'street' => t('Street, sidewalk, highway, trail, or other public right-of-way'),
        'private' => t('Private property, such as a home, apartment building, or business'),
        'park' => t('A public park, natural area, or city-operated community center'),
        'waterway' => t('A river, stream, or other waterway'),
        'other' => t('I\'m not sure')
      ],
      '#options_display' => 'one_column',
      // '#default_value' => 'street',
      '#attributes' => ['class' => ['location-type','visually-hidden-x']],
      '#required' => TRUE
    ];
    $element['location_type_hidden'] = [
      '#type' => 'hidden',
      '#title' => t('Location types'),
      '#attributes' => ['id' => 'location_type_hidden'],
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
    $element['location_private_owner'] = [
      '#id' => 'location_private_owner',
      '#type' => 'radios',
      '#title' => t('Are you the owner of the property?'),
      '#title_display' => 'before',
      '#options' => 'yes_no',
      '#options_display' => 'side_by_side',
      '#states' => [
        'visible' => [
          ':input[name="' . $element_id . '[location_type_taxlot]"]' => [
            'value' => '1'
          ],
          ':input[name="' . $element_id . '[location_type_park]"]' => [
            '!value' => '1'
          ],
        ],
        'required' => [
          ':input[name="' . $element_id . '[location_type_taxlot]"]' => [
            'value' => '1'
          ],
          ':input[name="' . $element_id . '[location_type_park]"]' => [
            '!value' => '1'
          ],
        ],
      ],
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

    $location_required_error = "Location is required. Please select a location by searching or clicking the map.";
    $primaryLayerBehavior = array_key_exists('#primary_layer_behavior', $element) ? $element['#primary_layer_behavior'] : "";
    $primaryLayerType = array_key_exists('#primary_layer_type', $element) ? $element['#primary_layer_type'] : "";

    if ($primaryLayerBehavior == "selection-only" && $primaryLayerType == "assets") {
      $location_required_error = "Please select an asset on the map that you'd like to report. You may need to zoom in to see asset markers, or there may not be any reportable assets within view.";
    }

    $element['location_lat'] = [
      '#type' => 'hidden',
      '#title' => t('Location'),
      '#title_display' => 'invisible',
      '#attributes' => ['class' => ['location-lat'], 'id' => 'location_lat'],
      '#required_error' => $location_required_error,
    ];
    // we're using "lng" everywhere else since that's what Leaflet uses, but this field is already
    // referenced in too many config files from webform handlers, so this is the one place it will
    // remain "lon"...
    $element['location_lon'] = [
      '#type' => 'hidden',
      '#title' => t('Longitude'),
      '#title_display' => 'invisible',
      '#attributes' => ['class' => ['location-lng'], 'id' => 'location_lon'],
    ];
    $element['location_x'] = [
      '#type' => 'hidden',
      '#title' => t('Web Mercator x coordinate'),
      '#title_display' => 'invisible',
      '#attributes' => ['class' => ['location-x'], 'id' => 'location_x'],
    ];
    $element['location_y'] = [
      '#type' => 'hidden',
      '#title' => t('Web Mercator y coordinate'),
      '#title_display' => 'invisible',
      '#attributes' => ['class' => ['location-y'], 'id' => 'location_y'],
    ];
    $element['place_name'] = [
      '#type' => 'textfield',
      '#id' => 'place_name',
      '#title' => t('Location Name'),
      '#attributes' => ['class' => ['place-name']],
      '#description' => t('If this location has a name, such as a business or public building, please enter it here.'),
      '#description_display' => 'before',
    ];
    $element['location_details'] = [
      '#type' => 'textarea',
      '#id' => 'location_details',
      '#title' => t('Location Details'),
      '#attributes' => ['class' => ['location-details']],
      '#description' => t('Please provide any other details that might help us locate the site you are reporting.'),
      '#description_display' => 'before',
    ];
    $element['location_attributes'] = [
      '#type' => 'hidden',
      '#title' => t('Location Attributes'),
      '#attributes' => ['class' => ['location-attributes'], 'id' => 'location_attributes'],
    ];
    $element['location_asset_id'] = [
      '#type' => 'hidden',
      '#title' => t('Asset ID'),
      '#title_display' => 'invisible',
      '#attributes' => ['class' => ['location-asset-id'], 'id' => 'location_asset_id'],
    ];
    $element['location_region_id'] = [
      '#type' => 'hidden',
      '#title' => t('Region ID'),
      '#title_display' => 'invisible',
      '#attributes' => ['class' => ['location-region-id'], 'id' => 'location_region_id'],
    ];
    // not currently in use. might be used if multiple municipalities are valid, such as for the water bureau.
    $element['location_municipality_name'] = [
      '#type' => 'hidden',
      '#title' => t('Municipality Name'),
      '#title_display' => 'invisible',
      '#attributes' => ['class' => ['location-municipality-name'], 'id' => 'location_municipality_name'],
    ];
    $element['location_is_portland'] = [
      '#type' => 'hidden',
      '#title' => t('Within the configured geofence (typcially Portland)?'),
      '#title_display' => 'invisible',
      '#attributes' => ['class' => ['location-is-portland'], 'id' => 'location_is_portland'],
      '#default_value' => "TRUE"
    ];
    $element['geojson_layer'] = [
      '#title' => t('GeoJson Layer'),
      '#type' => 'hidden',
      '#id' => 'geojson_layer',
    ];
    $element['geojson_layer_behavior'] = [
      '#title' => t('GeoJson Layer Behavior'),
      '#type' => 'hidden',
      '#id' => 'geojson_layer_behavior',
    ];

    return $element;
  }
}
