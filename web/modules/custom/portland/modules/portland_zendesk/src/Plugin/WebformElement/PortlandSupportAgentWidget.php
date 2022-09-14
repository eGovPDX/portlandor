<?php

namespace Drupal\portland_zendesk\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'portland_support_agent_widget' element.
 *
 * @WebformElement(
 *   id = "portland_support_agent_widget",
 *   label = @Translation("Portland support agent widget"),
 *   description = @Translation("Provides a composite element for identifying the logged-in employee who completed the form and linking the resulting Zendesk request to an interaction request.'"),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\portland_zendesk\Element\PortlandSupportAgentWidget
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class PortlandSupportAgentWidget extends WebformCompositeBase {

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
    $lines[] = "<h2>Customer Service Details</h2>";
    if (isset($value['employee_email']) && $value['employee_email']) {
      $lines[] = 'Form submitted by: ' . $value['employee_email'] . '<br>';
    }
    if (isset($value['zendesk_request_number']) && $value['zendesk_request_number']) {
      $lines[] = 'Zendesk request number: ' . $value['zendesk_request_number'] . '<br>';
    }
    if (isset($value['test_submission']) && $value['test_submission']) {
      $lines[] = 'Test submission? ' . $value['test_submission'] . '<br>';
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
    $lines[] = "Customer Service Details:";
    if (isset($value['employee_email']) && $value['employee_email']) {
      $lines[] = 'Form submitted by: ' . $value['employee_email'] . '<br>';
    }
    if (isset($value['zendesk_request_number']) && $value['zendesk_request_number']) {
      $lines[] = 'Zendesk request number: ' . $value['zendesk_request_number'] . '<br>';
    }
    if (isset($value['test_submission']) && $value['test_submission']) {
      $lines[] = 'Test submission? ' . $value['test_submission'] . '<br>';
    }
    return $lines;
  }

  //   /**
  //  * {@inheritdoc}
  //  */
  // public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
  //   parent::prepare($element, $webform_submission);

  //   $primaryLayerSource = array_key_exists('#primary_layer_source', $element) ? $element['#primary_layer_source'] : "";
  //   $incidentsLayerSource = array_key_exists('#incidents_layer_source', $element) ? $element['#incidents_layer_source'] : "";
  //   $primaryLayerBehavior = array_key_exists('#primary_layer_behavior', $element) ? $element['#primary_layer_behavior'] : "";
  //   $primaryLayerType = array_key_exists('#primary_layer_type', $element) ? $element['#primary_layer_type'] : "";
  //   $primaryMarker = array_key_exists('#primary_marker', $element) ? $element['#primary_marker'] : "";
  //   $selectedMarker = array_key_exists('#selected_marker', $element) ? $element['#selected_marker'] : "";
  //   $incidentMarker = array_key_exists('#incident_marker', $element) ? $element['#incident_marker'] : "";
  //   $disablePopup = array_key_exists('#disable_popup', $element) && $element['#disable_popup'] ? 1 : 0;
  //   $verifyButtonText = array_key_exists('#verify_button_text', $element) ? $element['#verify_button_text'] : "";
  //   $primaryFeatureName = array_key_exists('#primary_feature_name', $element) ? $element['#primary_feature_name'] : "";
  //   $featureLayerVisibleZoom = array_key_exists('#feature_layer_visible_zoom', $element) ? $element['#feature_layer_visible_zoom'] : "";

  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_layer_source'] = $primaryLayerSource;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['incidents_layer_source'] = $incidentsLayerSource;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_layer_behavior'] = $primaryLayerBehavior;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_layer_type'] = $primaryLayerType;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_marker'] = $primaryMarker;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['selected_marker'] = $selectedMarker;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['incident_marker'] = $incidentMarker;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['disable_popup'] = $disablePopup;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['verify_button_text'] = $verifyButtonText;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['primary_feature_name'] = $primaryFeatureName;
  //   $element['#attached']['drupalSettings']['webform']['portland_location_picker']['feature_layer_visible_zoom'] = $featureLayerVisibleZoom;
  // }

}
