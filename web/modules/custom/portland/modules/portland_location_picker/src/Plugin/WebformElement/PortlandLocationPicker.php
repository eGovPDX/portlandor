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
 *   description = @Translation("Provides an element for selecting a location by map or address and storing the lat/lon coordiantes. WARNING: Only one instance of a location picker widget is supported in a webform, and the machine name must be 'report_location.'"),
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
    if ($value['location_type']) {
      $lines[] = 'Location type: ' . $value['location_type'] . '<br>';
    }
    if ($value['place_name']) {
      $lines[] = 'Place name: ' . $value['place_name'] . '<br>';
    }
    if ($value['location_address']) {
      $lines[] = 'Address: <a href="https://www.google.com/maps/place/' . $value['location_address'] . '">' . $value['location_address'] . '</a>';
    }
    if ($value['location_lat'] && $value['location_lon']) {
      $latlon = $value['location_lat'] . ',' . $value['location_lon'];
      $lines[] = 'Lat/lon: <a href="https://www.google.com/maps/place/' . $latlon . '">' . $latlon . '</a><br>';
    }
    if ($value['location_details']) {
      $lines[] = 'Location details: ' . $value['location_details'] . '<br>';
    }
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
    if ($value['location_type']) {
      $lines[] = 'Location type: ' . $value['location_type'];
    }
    if ($value['place_name']) {
      $lines[] = 'Place name: ' . $value['place_name'];
    }
    if ($value['location_address']) {
      $lines[] = 'Address: ' . $value['location_address'];
    }
    if ($value['location_lat']) {
      $lines[] = 'Lat: ' . $value['location_lat'];
    }
    if ($value['location_lon']) {
      $lines[] = 'Lon: ' . $value['location_lon'];
    }
    if ($value['location_details']) {
      $lines[] = 'Location details: ' . $value['location_details'];
    }
    return $lines;
  }

    /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $layerUrl = $element['#geojson_layer'] ?? NULL;
    $layerBehavior = $element['#geojson_layer_behavior'] ?? NULL;
    $layerType = $element['#geojson_layer_type'] ?? NULL;

    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['geojson_layer'] = $layerUrl;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['geojson_layer_behavior'] = $layerBehavior;
    $element['#attached']['drupalSettings']['webform']['portland_location_picker']['geojson_layer_type'] = $layerType;

  }

}
