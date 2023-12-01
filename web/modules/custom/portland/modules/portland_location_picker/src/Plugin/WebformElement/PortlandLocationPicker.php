<?php

namespace Drupal\portland_location_picker\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'portland_location_picker' element.
 *
 * @WebformElement(
 *   id = "portland_location_picker",
 *   label = @Translation("Portland location picker"),
 *   description = @Translation("Provides an element for selecting a location by map or address and storing the lat/lng coordiantes. WARNING: Only one instance of a location picker widget is supported in a webform, and the machine name must be 'report_location.'"),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\portland_location_picker\Element\PortlandLocationPicker
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class PortlandLocationPicker extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // this text string is used as a display value for the field value, and is what is returned by the parent
    // level token, such as [webform_submission:values:location]. If more granular field sub-field values are
    // needed, such as in a handler that is sending data to an external system, the sub-field needs to be
    // specified in the token, such as [webform_submission:values:location:place_name].
    $lines = [];
    $lines[] = '<p>';

    if (isset($value['place_name']) && $value['place_name']) {
      $lines[] = '<strong>Location name:</strong> ' . $value['place_name'];
    }
    if (isset($value['location_address']) && $value['location_address']) {
      $lines[] = '<strong>Address:</strong>&nbsp;<a href="https://www.google.com/maps/place/' . $value['location_address'] . '">' . $value['location_address'] . '</a>';
    }
    if (isset($value['location_lat']) && isset($value['location_lon']) && $value['location_lat']) {
      $latlon = $value['location_lat'] . ',' . $value['location_lon'];
      $lines[] = '<strong>Lat/lng:</strong>&nbsp;<a href="https://www.google.com/maps/place/' . $latlon . '">' . $latlon . '</a>';
    }
    if (isset($value['location_municipality_name']) && $value['location_municipality_name']) {
      $lines[] = '<strong>Municipality:</strong> ' . $value['location_municipality_name'];
    }
    if (isset($value['location_zipcode']) && $value['location_zipcode']) {
      $lines[] = '<strong>Zipcode:</strong> ' . $value['location_zipcode'];
    }
    if (isset($value['location_details']) && $value['location_details']) {
      $lines[] = '<strong>Location details:</strong> ' . $value['location_details'];
    }
    if (isset($value['location_types']) && $value['location_types']) {
      $lines[] = '<strong>Location type(s):</strong> ' . $value['location_types'];
    }
    if (isset($value['location_attributes']) && $value['location_attributes']) {
      $lines[] = '<strong>Location details:</strong> ' . $value['location_attributes'];
    }
    if (isset($value['location_asset_id']) && $value['location_asset_id']) {
      $lines[] = '<strong>Asset ID:</strong> ' . $value['location_asset_id'];
    }
    if (isset($value['location_region_id']) && $value['location_region_id']) {
      $lines[] = '<strong>Region ID:</strong> ' . $value['location_region_id'];
    }
    if (isset($value['location_search']) && $value['location_search']) {
      $lines[] = '<strong>Search query:</strong> ' . $value['location_search'];
    }
    $lines[] = '</p>';
    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // this text string is used as a display value for the field value, and is what is returned by the parent
    // level token, such as [webform_submission:values:location]. If more granular field sub-field values are
    // needed, such as in a handler that is sending data to an external system, the sub-field needs to be
    // specified in the token, such as [webform_submission:values:location:place_name].
    $lines = [];
    $lines[] = '';
    if (isset($value['place_name']) && $value['place_name']) {
      $lines[] = 'Location name: ' . $value['place_name'];
    }
    if (isset($value['location_address']) && $value['location_address']) {
      $lines[] = 'Address: ' . $value['location_address'];
      $lines[] = 'Map link: https://www.google.com/maps/place/' . $value['location_address'];
    }
    if (isset($value['location_lat']) && isset($value['location_lon']) && $value['location_lat']) {
      $latlon = $value['location_lat'] . ',' . $value['location_lon'];
      $lines[] = 'Lat/lng: ' . $latlon;
      $lines[] = 'Map link: https://www.google.com/maps/place/' . $latlon;
    }
    if (isset($value['location_municipality_name']) && $value['location_municipality_name']) {
      $lines[] = 'Municipality: ' . $value['location_municipality_name'];
    }
    if (isset($value['location_zipcode']) && $value['location_zipcode']) {
      $lines[] = 'Region ID: ' . $value['location_zipcode'];
    }
    if (isset($value['location_details']) && $value['location_details']) {
      $lines[] = 'Location details: ' . $value['location_details'];
    }
    if (isset($value['location_types']) && $value['location_types']) {
      $lines[] = 'Location type(s): ' . $value['location_types'];
    }    
    if (isset($value['location_attributes']) && $value['location_attributes']) {
      $lines[] = 'Location details: ' . $value['location_attributes'];
    }
    if (isset($value['location_asset_id']) && $value['location_asset_id']) {
      $lines[] = 'Asset ID: ' . $value['location_asset_id'];
    }
    if (isset($value['location_region_id']) && $value['location_region_id']) {
      $lines[] = 'Region ID: ' . $value['location_region_id'];
    }
    if (isset($value['location_search']) && $value['location_search']) {
      $lines[] = 'Region ID: ' . $value['location_search'];
    }
    return $lines;
  }

    /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element_id = "report_location";
    
    if (array_key_exists("#webform_key", $element)) {
      $element_id = $element['#webform_key'];
    }

    $addressVerify = array_key_exists('#address_verify', $element) ? $element['#address_verify'] : FALSE;
    $primaryLayerSource = array_key_exists('#primary_layer_source', $element) ? $element['#primary_layer_source'] : "";
    $incidentsLayerSource = array_key_exists('#incidents_layer_source', $element) ? $element['#incidents_layer_source'] : "";
    $regionsLayerSource = array_key_exists('#regions_layer_source', $element) ? $element['#regions_layer_source'] : "";
    $primaryLayerBehavior = array_key_exists('#primary_layer_behavior', $element) ? $element['#primary_layer_behavior'] : "";
    $primaryLayerType = array_key_exists('#primary_layer_type', $element) ? $element['#primary_layer_type'] : "";
    $primaryMarker = array_key_exists('#primary_marker', $element) ? $element['#primary_marker'] : "";
    $selectedMarker = array_key_exists('#selected_marker', $element) ? $element['#selected_marker'] : "";
    $incidentMarker = array_key_exists('#incident_marker', $element) ? $element['#incident_marker'] : "";
    $disablePopup = array_key_exists('#disable_popup', $element) && $element['#disable_popup'] ? 1 : 0;
    $verifyButtonText = array_key_exists('#verify_button_text', $element) ? $element['#verify_button_text'] : ($addressVerify ? "Verify" : "Search");
    $primaryFeatureName = array_key_exists('#primary_feature_name', $element) ? $element['#primary_feature_name'] : "";
    $featureLayerVisibleZoom = array_key_exists('#feature_layer_visible_zoom', $element) ? $element['#feature_layer_visible_zoom'] : "";
    $displayCityLimits = array_key_exists('#display_city_limits', $element) ? $element['#display_city_limits'] : TRUE;
    $requireCityLimits = array_key_exists('#require_city_limits', $element) ? $element['#require_city_limits'] : FALSE;
    $requireCityLimitsPlusParks = array_key_exists('#require_city_limits_plus_parks', $element) ? $element['#require_city_limits_plus_parks'] : FALSE;
    $locationTypes = array_key_exists('#location_types', $element) ? $element['#location_types'] : 'park,row,stream,street,taxlot,trail,waterbody';
    $disablePlaceNameAutofill = array_key_exists('#disable_place_name_autofill', $element) ? $element['#disable_place_name_autofill'] : FALSE;
    
    $boundaryUrl = array_key_exists('#boundary_url', $element) ? $element['#boundary_url'] : 'https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query?where=1%3D1&objectIds=35&outFields=*&returnGeometry=true&f=geojson';
    $displayBoundary = array_key_exists('#display_boundary', $element) ? $element['#display_boundary'] : TRUE;
    $requireBoundary = array_key_exists('#require_boundary', $element) ? $element['#require_boundary'] : FALSE;
    $outOfBoundsMessage = array_key_exists('#out_of_bounds_message', $element) ? $element['#out_of_bounds_message'] : "The location you selected is not within our service area. Please try a different location.";

    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['address_verify'] = $addressVerify;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['element_id'] = $element_id;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_layer_source'] = $primaryLayerSource;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['incidents_layer_source'] = $incidentsLayerSource;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['regions_layer_source'] = $regionsLayerSource;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_layer_behavior'] = $primaryLayerBehavior;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_layer_type'] = $primaryLayerType;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_marker'] = $primaryMarker;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['selected_marker'] = $selectedMarker;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['incident_marker'] = $incidentMarker;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['disable_popup'] = $disablePopup;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['verify_button_text'] = $verifyButtonText;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_feature_name'] = $primaryFeatureName;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['feature_layer_visible_zoom'] = $featureLayerVisibleZoom;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['display_city_limits'] = $displayCityLimits;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['require_city_limits'] = $requireCityLimits;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['require_city_limits_plus_parks'] = $requireCityLimitsPlusParks;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['location_types'] = $locationTypes;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['disable_place_name_autofill'] = $disablePlaceNameAutofill;

    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['boundary_url'] = $boundaryUrl;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['display_boundary'] = $displayBoundary;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['require_boundary'] = $requireBoundary;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['out_of_bounds_message'] = $outOfBoundsMessage;
  }

}
