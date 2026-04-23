<?php

namespace Drupal\portland_location_picker\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Html;

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
    $e = static fn($s) => Html::escape($s);

    // This string is used for the composite's display value via tokens like
    // [webform_submission:values:location]. Use sub-field tokens for granular values.
    $lines = [];

    if (!empty($value['place_name'])) {
      $lines[] = '<strong>Location name:</strong> ' . $e($value['place_name']);
    }

    if (!empty($value['location_address'])) {
      $addr = $e($value['location_address']);
      $lines[] = '<strong>Address:</strong>&nbsp;<a href="https://www.google.com/maps/place/' . $addr . '">' . $addr . '</a>';
    }

    if (!empty($value['location_lat']) && isset($value['location_lon'])) {
      $latlon = $e($value['location_lat'] . ',' . $value['location_lon']);
      $lines[] = '<strong>Lat/lng:</strong>&nbsp;<a href="https://www.google.com/maps/place/' . $latlon . '">' . $latlon . '</a>';
    }

    if (!empty($value['location_municipality_name'])) {
      $lines[] = '<strong>Municipality:</strong> ' . $e($value['location_municipality_name']);
    }

    if (!empty($value['location_zipcode'])) {
      $lines[] = '<strong>Zipcode:</strong> ' . $e($value['location_zipcode']);
    }

    if (!empty($value['location_details'])) {
      $lines[] = '<strong>Location details:</strong> ' . $e($value['location_details']);
    }

    if (!empty($value['location_types'])) {
      $lines[] = '<strong>Location type(s):</strong> ' . $e($value['location_types']);
    }

    if (!empty($value['location_attributes'])) {
      $lines[] = '<strong>Location attributes:</strong> ' . $e($value['location_attributes']);
    }

    if (!empty($value['location_asset_id'])) {
      $lines[] = '<strong>Asset ID:</strong> ' . $e($value['location_asset_id']);
    }

    if (!empty($value['location_region_id'])) {
      $lines[] = '<strong>Region ID:</strong> ' . $e($value['location_region_id']);
    }

    if (!empty($value['location_search'])) {
      $lines[] = '<strong>Search query:</strong> ' . $e($value['location_search']);
    }

    // IMPORTANT for composites: return a LIST of render arrays.
    // Here we return a single item containing the full block.
    return [
      [
        '#markup' => Markup::create('<p>' . implode('<br />', $lines) . '</p>'),
      ],
    ];
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
      $lines[] = 'Search query: ' . $value['location_search'];
    }
    return $lines;
  }

    /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
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
    $verifyButtonText = array_key_exists('#verify_button_text', $element) ? $element['#verify_button_text'] : ($addressVerify ? $this->t("Verify") : $this->t("Search"));
    $primaryFeatureName = array_key_exists('#primary_feature_name', $element) ? $element['#primary_feature_name'] : "";
    $featureLayerVisibleZoom = array_key_exists('#feature_layer_visible_zoom', $element) ? $element['#feature_layer_visible_zoom'] : "";
    $displayCityLimits = array_key_exists('#display_city_limits', $element) ? $element['#display_city_limits'] : TRUE;
    $requireCityLimits = array_key_exists('#require_city_limits', $element) ? $element['#require_city_limits'] : FALSE;
    $requireCityLimitsPlusParks = array_key_exists('#require_city_limits_plus_parks', $element) ? $element['#require_city_limits_plus_parks'] : FALSE;
    $locationTypes = array_key_exists('#location_types', $element) ? $element['#location_types'] : 'park,row,stream,street,taxlot,trail,waterbody';
    $disablePlaceNameAutofill = array_key_exists('#disable_place_name_autofill', $element) ? $element['#disable_place_name_autofill'] : FALSE;
    $regionIdPropertyName = array_key_exists('#region_id_property_name', $element) ? $element['#region_id_property_name'] : 'region_id';
    $maxZoom = array_key_exists('#max_zoom', $element) ? $element['#max_zoom'] : 18;

    $boundaryUrl = array_key_exists('#boundary_url', $element) ? $element['#boundary_url'] : 'https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query?where=1%3D1&objectIds=35&outFields=*&returnGeometry=true&f=geojson';
    $displayBoundary = array_key_exists('#display_boundary', $element) ? $element['#display_boundary'] : TRUE;
    $requireBoundary = array_key_exists('#require_boundary', $element) ? $element['#require_boundary'] : FALSE;
    $outOfBoundsMessage = array_key_exists('#out_of_bounds_message', $element) ? $element['#out_of_bounds_message'] : "The location you selected is not within our service area. Please try a different location.";

    // in this URL, the {{x}} and {{y}} tokens need to be replaced with real values.
    $clickQueryUrl = array_key_exists('#click_query_url', $element) ? $element['#click_query_url'] : '';
    $clickQueryPropertyPath = array_key_exists('#click_query_property_path', $element) ? $element['#click_query_property_path'] : '';
    $clickQueryDestinationField = array_key_exists('#click_query_destination_field', $element) ? $element['#click_query_destination_field'] : 'location_region_id';

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
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['region_id_property_name'] = $regionIdPropertyName;


    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['boundary_url'] = $boundaryUrl;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['display_boundary'] = $displayBoundary;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['require_boundary'] = $requireBoundary;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['out_of_bounds_message'] = $outOfBoundsMessage;

    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['click_query_url'] = $clickQueryUrl;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['click_query_property_path'] = $clickQueryPropertyPath;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['click_query_destination_field'] = $clickQueryDestinationField;

    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['max_zoom'] = $maxZoom;

    $latRequired = !empty($element['#location_lat__required'])
      || (!empty($element['location_lat'])
        && (!empty($element['location_lat']['#required']) || !empty($element['location_lat']['#_required'])));
    $lonRequired = !empty($element['#location_lon__required'])
      || (!empty($element['location_lon'])
        && (!empty($element['location_lon']['#required']) || !empty($element['location_lon']['#_required'])));
    $isLocationRequired = $latRequired || $lonRequired;

    // Keep a stable location-required flag for rendering cues that should not
    // depend on Webform moving #required <-> #_required during states handling.
    $element['#location_selection_required'] = $isLocationRequired;

    // location_search should never be required on its own. Users can satisfy
    // location requirements by searching OR clicking the map, so ignore any
    // location_search required flags set in UI config.
    $element['#location_search__required'] = FALSE;
    if (!empty($element['location_search']) && isset($element['location_search']['#type'])) {
      $element['location_search']['#required'] = FALSE;
      $element['location_search']['#_required'] = FALSE;
    }
    
    // Keep composite-level required behavior only when explicitly configured on
    // the composite itself. Do not promote hidden lat/lon required flags to
    // composite #required, because conditional #states handling can then mark
    // visible sub-element labels as required incorrectly.
    $compositeExplicitRequired = !empty($element['#required']) || !empty($element['#_required']);
    if ($compositeExplicitRequired) {
      $element['#required'] = TRUE;
      $element['#attributes']['aria-required'] = 'true';
      $element['#wrapper_attributes']['aria-required'] = 'true';
    }
    elseif ($isLocationRequired) {
      // Preserve ARIA cue for the overall interaction without triggering
      // composite required rendering behavior.
      $element['#attributes']['aria-required'] = 'true';
      $element['#wrapper_attributes']['aria-required'] = 'true';
    }

    // If location_lat or location_lon was marked required (via YAML shorthand or direct
    // #required), clear the built-in required flag so Drupal doesn't try to
    // validate or scroll to a hidden input. Instead, register a validator on
    // the composite that can attach the error to a visible part of the widget.
    if ($isLocationRequired) {
      $element['#location_lat__required'] = FALSE;
      $element['#location_lon__required'] = FALSE;
      // Avoid creating a sparse location_lat child override before Webform has
      // initialized the composite sub-elements, otherwise the real hidden
      // element definition can be replaced and disappear from rendered markup.
      if (!empty($element['location_lat']) && isset($element['location_lat']['#type'])) {
        $element['location_lat']['#required'] = FALSE;
      }
      if (!empty($element['location_lon']) && isset($element['location_lon']['#type'])) {
        $element['location_lon']['#required'] = FALSE;
      }
      $element['#element_validate'][] = [static::class, 'validateLocationRequired'];
    }
  }

  /**
   * Validates that a location has been selected when location_lat is required.
   *
   * Registers the error on the composite element rather than the hidden
   * location_lat sub-element, so Drupal's scroll-to-error behaviour works.
   */
  public static function validateLocationRequired(array &$element, FormStateInterface $form_state, array &$form) {
    // Use the element's parents path so nested elements validate correctly.
    $parents = $element['#parents'] ?? NULL;
    if ($parents === NULL) {
      // Fallback for cases where #parents is not available.
      $key = $element['#webform_key'] ?? 'report_location';
      $parents = [$key];
    }
    $values = $form_state->getValue($parents);
    $lat = is_array($values) ? ($values['location_lat'] ?? '') : '';

    if (!empty($lat) && $lat !== '0') {
      return;
    }

    $message = !empty($element['location_lat']['#required_error'])
      ? $element['location_lat']['#required_error']
      : t('Location is required. Please select a location by searching or clicking the map.');

    // Register this error against the visible map child instead of the
    // composite wrapper to avoid the same message being repeated for each
    // sub-element by composite error rendering.
    $error_element = $element['location_map'] ?? $element;
    $form_state->setError($error_element, $message);
  }

}
