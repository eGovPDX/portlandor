<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'portland_location_widget' element.
 *
 * @WebformElement(
 *   id = "portland_location_widget",
 *   label = @Translation("Portland location widget"),
 *   description = @Translation("Provides an element for selecting a location by map or address."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class PortlandLocationWidget extends WebformCompositeBase {

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
    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // get the element ID as it's defined in the webform
    $element_id = $element['#webform_key'];
    if (array_key_exists("#webform_key", $element)) {
      $element_id = $element['#webform_key'];
    }
    $element['#attached']['drupalSettings']['webform']['portland_location_widget'][$element_id]['id'] = $element_id;

    // get custom properties as they're defined in the element's Advanced config tab Custom Settings field 
    $mapLayers = array_key_exists('#layers', $element) ? $element['#layers'] : "";
    $element['#attached']['drupalSettings']['webform']['portland_location_widget'][$element_id]['layers'] = $mapLayers;

    $primaryBoundary = array_key_exists('#primary_boundary', $element) ? $element['#primary_boundary'] : "";
    $element['#attached']['drupalSettings']['webform']['portland_location_widget'][$element_id]['primary_boundary'] = $primaryBoundary;
  }
}
