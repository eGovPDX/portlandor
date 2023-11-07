<?php

namespace Drupal\portland_webform\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'portland_webform_address_verifier' element.
 *
 * @WebformElement(
 *   id = "portland_webform_address_verifier",
 *   label = @Translation("Portland webform address verifier"),
 *   description = @Translation("Provides an element for searching for and verifying addresses within the City of Portland and surrounding municipalities."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 */
class PortlandWebformAddressVerifier extends WebformCompositeBase {

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
    $lines[] = '<br>';
    // if (isset($value['location_type']) && $value['location_type']) {
    //   $lines[] = '<strong>Selected location type:</strong> ' . $value['location_type'];
    // }
    // if (isset($value['place_name']) && $value['place_name']) {
    //   $lines[] = '<strong>Location name:</strong> ' . $value['place_name'];
    // }
    // if (isset($value['location_address']) && $value['location_address']) {
    //   $lines[] = '<strong>Address:</strong> <a href="https://www.google.com/maps/place/' . $value['location_address'] . '">' . $value['location_address'] . '</a>';
    // }
    // if (isset($value['location_private_owner']) && $value['location_private_owner']) {
    //   $lines[] = '<strong>Are you the owner?</strong> ' . $value['location_private_owner'];
    // }
    // if (isset($value['location_details']) && $value['location_details']) {
    //   $lines[] = '<strong>Location details:</strong> ' . $value['location_details'];
    // }
    // if (isset($value['location_attributes']) && $value['location_attributes']) {
    //   $lines[] = '<strong>Location details:</strong> ' . $value['location_attributes'];
    // }
    // if (isset($value['location_type_hidden']) && $value['location_type_hidden']) {
    //   $lines[] = '<strong>Location type code(s):</strong> ' . $value['location_type_hidden'];
    // }    
    // if (isset($value['location_asset_id']) && $value['location_asset_id']) {
    //   $lines[] = '<strong>Asset ID:</strong> ' . $value['location_asset_id'];
    // }
    // if (isset($value['location_region_id']) && $value['location_region_id']) {
    //   $lines[] = '<strong>Region ID:</strong> ' . $value['location_region_id'];
    // }
    // if (isset($value['location_municipality_name']) && $value['location_municipality_name']) {
    //   $lines[] = '<strong>Municipality:</strong> ' . $value['location_municipality_name'];
    // }
    // if (isset($value['location_is_portland']) && $value['location_is_portland']) {
    //   $lines[] = '<strong>Is Portland:</strong> ' . $value['location_is_portland'];
    // }
    // if (isset($value['location_lat']) && isset($value['location_lon']) && $value['location_lat']) {
    //   $latlon = $value['location_lat'] . ',' . $value['location_lon'];
    //   $lines[] = '<strong>Lat/lng:</strong> <a href="https://www.google.com/maps/place/' . $latlon . '">' . $latlon . '</a>';
    // }
    // if (isset($value['location_x']) && isset($value['location_y']) && $value['location_y']) {
    //   $lines[] = '<strong>X/Y coords:</strong> ' . $value['location_x'] . ' / ' . $value['location_y'];
    // }
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
    // if (isset($value['location_type']) && $value['location_type']) {
    //   $lines[] = 'Selected location type: ' . $value['location_type'];
    // }
    // if (isset($value['place_name']) && $value['place_name']) {
    //   $lines[] = 'Location name: ' . $value['place_name'];
    // }
    // if (isset($value['location_address']) && $value['location_address']) {
    //   $lines[] = 'Address: ' . $value['location_address'];
    //   $lines[] = 'Map link: https://www.google.com/maps/place/' . $value['location_address'];
    // }
    // if (isset($value['location_private_owner']) && $value['location_private_owner']) {
    //   $lines[] = 'Are you the owner? ' . $value['location_private_owner'];
    // }
    // if (isset($value['location_details']) && $value['location_details']) {
    //   $lines[] = 'Location details: ' . $value['location_details'];
    // }
    // if (isset($value['location_attributes']) && $value['location_attributes']) {
    //   $lines[] = 'Location details: ' . $value['location_attributes'];
    // }
    // if (isset($value['location_type_hidden']) && $value['location_type_hidden']) {
    //   $lines[] = 'Location type code(s): ' . $value['location_type_hidden'];
    // }    
    // if (isset($value['location_asset_id']) && $value['location_asset_id']) {
    //   $lines[] = 'Asset ID: ' . $value['location_asset_id'];
    // }
    // if (isset($value['location_region_id']) && $value['location_region_id']) {
    //   $lines[] = 'Region ID: ' . $value['location_region_id'];
    // }
    // if (isset($value['location_municipality_name']) && $value['location_municipality_name']) {
    //   $lines[] = 'Municipality: ' . $value['location_municipality_name'];
    // }
    // if (isset($value['location_is_portland']) && $value['location_is_portland']) {
    //   $lines[] = 'Is Portland: ' . $value['location_is_portland'];
    // }
    // if (isset($value['location_lat']) && isset($value['location_lon']) && $value['location_lat']) {
    //   $latlon = $value['location_lat'] . ',' . $value['location_lon'];
    //   $lines[] = 'Lat/lng: ' . $latlon;
    //   $lines[] = 'Map link: https://www.google.com/maps/place/' . $latlon;
    // }
    // if (isset($value['location_x']) && isset($value['location_y']) && $value['location_y']) {
    //   $lines[] = 'X/Y coords: ' . $value['location_x'] . ' / ' . $value['location_y'];
    // }
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

    $useAddressLine2 = array_key_exists('#use_address_line2', $element) ? $element['#use_address_line2'] : FALSE;
    $defaultState = array_key_exists('#default_state', $element) ? $element['#default_state'] : 'OR';
    $verifyButtonText = array_key_exists('#default_state', $element) ? $element['#verify_button_text'] : 'Verify';

    $element['#attached']['drupalSettings']['webform']['portland_webform_address_verifier']['use_address_line2'] = $useAddressLine2;
    $element['#attached']['drupalSettings']['webform']['portland_webform_address_verifier']['default_state'] = $defaultState;
    $element['#attached']['drupalSettings']['webform']['portland_webform_address_verifier']['verify_button_text'] = $verifyButtonText;
  }

}
