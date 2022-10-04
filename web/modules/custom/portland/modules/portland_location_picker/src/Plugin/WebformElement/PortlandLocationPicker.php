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
    $lines[] = "<p>";
    if (isset($value['location_type']) && $value['location_type']) {
      $lines[] = 'Location type: ' . $value['location_type'] . '<br>';
    }
    if (isset($value['location_private_owner']) && $value['location_private_owner']) {
      $lines[] = 'Are you the owner? ' . $value['location_private_owner'] . '<br>';
    }
    if (isset($value['place_name']) && $value['place_name']) {
      $lines[] = 'Location name: ' . $value['place_name'] . '<br>';
    }
    if (isset($value['location_address']) && $value['location_address']) {
      $lines[] = 'Address: <a href="https://www.google.com/maps/place/' . $value['location_address'] . '">' . $value['location_address'] . '</a>';
    }
    if (isset($value['location_lat']) && isset($value['location_lng']) && $value['location_lat']) {
      $latlon = $value['location_lat'] . ',' . $value['location_lng'];
      $lines[] = 'Lat/lng: <a href="https://www.google.com/maps/place/' . $latlon . '">' . $latlon . '</a><br>';
    }
    if (isset($value['location_details']) && $value['location_details']) {
      $lines[] = 'Details: ' . $value['location_details'] . '<br>';
    }
    if (isset($value['location_asset_id']) && $value['location_asset_id']) {
      $lines[] = 'Asset ID: ' . $value['location_asset_id'] . '<br>';
    }
    if (isset($value['location_region_id']) && $value['location_region_id']) {
      $lines[] = 'Region ID: ' . $value['location_region_id'] . '<br>';
    }
    $lines[] = "</p>";
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
    if (isset($value['location_type']) && $value['location_type']) {
      $lines[] = 'Location type: ' . $value['location_type'];
    }
    if (isset($value['location_private_owner']) && $value['location_private_owner']) {
      $lines[] = 'Are you the owner? ' . $value['location_private_owner'];
    }
    if (isset($value['place_name']) && $value['place_name']) {
      $lines[] = 'Location name: ' . $value['place_name'];
    }
    if (isset($value['location_address']) && $value['location_address']) {
      $lines[] = 'Address: ' . $value['location_address'];
    }
    if (isset($value['location_lat']) && $value['location_lat']) {
      $lines[] = 'Lat: ' . $value['location_lat'];
    }
    if (isset($value['location_lng']) && $value['location_lng']) {
      $lines[] = 'Lng: ' . $value['location_lng'];
    }
    if (isset($value['location_details']) && $value['location_details']) {
      $lines[] = 'Details: ' . $value['location_details'];
    }
    if (isset($value['location_asset_id']) && $value['location_asset_id']) {
      $lines[] = 'Asset ID: ' . $value['location_asset_id'];
    }
    if (isset($value['location_region_id']) && $value['location_region_id']) {
      $lines[] = 'Region ID: ' . $value['location_region_id'];
    }
    return $lines;
  }

    /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $primaryLayerSource = array_key_exists('#primary_layer_source', $element) ? $element['#primary_layer_source'] : "";
    $incidentsLayerSource = array_key_exists('#incidents_layer_source', $element) ? $element['#incidents_layer_source'] : "";
    $regionsLayerSource = array_key_exists('#regions_layer_source', $element) ? $element['#regions_layer_source'] : "";
    $primaryLayerBehavior = array_key_exists('#primary_layer_behavior', $element) ? $element['#primary_layer_behavior'] : "";
    $primaryLayerType = array_key_exists('#primary_layer_type', $element) ? $element['#primary_layer_type'] : "";
    $primaryMarker = array_key_exists('#primary_marker', $element) ? $element['#primary_marker'] : "";
    $selectedMarker = array_key_exists('#selected_marker', $element) ? $element['#selected_marker'] : "";
    $incidentMarker = array_key_exists('#incident_marker', $element) ? $element['#incident_marker'] : "";
    $disablePopup = array_key_exists('#disable_popup', $element) && $element['#disable_popup'] ? 1 : 0;
    $verifyButtonText = array_key_exists('#verify_button_text', $element) ? $element['#verify_button_text'] : "";
    $primaryFeatureName = array_key_exists('#primary_feature_name', $element) ? $element['#primary_feature_name'] : "";
    $featureLayerVisibleZoom = array_key_exists('#feature_layer_visible_zoom', $element) ? $element['#feature_layer_visible_zoom'] : "";
    $requireCityLimits = array_key_exists('#require_city_limits', $element) ? $element['#require_city_limits'] : TRUE;

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
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['require_city_limits'] = $requireCityLimits;
  }

}
