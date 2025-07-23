<?php

namespace Drupal\portland_address_verifier\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'portland_address_verifier' element.
 *
 * @WebformElement(
 *   id = "portland_address_verifier",
 *   label = @Translation("Portland Address Verifier"),
 *   description = @Translation("Provides an element for verifying an address in Portland."),
 *   category = @Translation("Portland elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\portland_address_verifier\Element\PortlandAddressVerifier
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class PortlandAddressVerifier extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#pre_render' => [
        [get_class($this), 'preRenderCompositeElement'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCompositeElement($element) {
    $element['#element_validate'][] = [get_class(), 'validateMyCompositeElement'];
    return $element;
  }

  /**
   * Custom validation handler.
   */
  public static function validateMyCompositeElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // Check if the element is visible based on your conditional logic.
    $visible = TRUE; // Replace this with your actual visibility check.

    if (!$visible) {
      // Loop through child elements and remove 'required' property if not visible.
      foreach ($element['#webform_composite_elements'] as $key => $child) {
        $form_state->setValueForElement($child, NULL);
        if (isset($element[$key]['#required']) && $element[$key]['#required']) {
          $element[$key]['#required'] = FALSE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // this content is used as a display value for the field value, and is what is returned by the parent
    // level token, such as [webform_submission:values:location]. If more granular field sub-field values are
    // needed, such as in a handler that is sending data to an external system, the sub-field needs to be
    // specified in the token, such as [webform_submission:values:location:place_name].
    $lines = [];
    $address = "";

    if ($value['location_verification_status'] == 'Verified' && array_key_exists('address_label', $value)) {
      $address = $value['address_label'];
      $address = str_replace("\r\n", "<br>", $address);
    } else {
      $address = $value['location_address'];
    }

    $lines[] = $address;
    //$lines[] = '<a href="https://www.google.com/maps/place/' . $value['location_address'] . '">' . $value['location_address'] . '</a>';
    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // this content is used as a display value for the field value, and is what is returned by the parent
    // level token, such as [webform_submission:values:location]. If more granular field sub-field values are
    // needed, such as in a handler that is sending data to an external system, the sub-field needs to be
    // specified in the token, such as [webform_submission:values:location:place_name].
    $lines = [];
    $address = "";

    if ($value['location_verification_status'] == 'Verified') {
      $address = $value['address_label'];
      $address = str_replace("<br>", "\r\n", $address);
    } else {
      $address = $value['location_address'];
    }

    $lines[] = $address;
    return $lines;
  }

    /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $key = isset($element['#webform_key']) ? $element['#webform_key'] : "";

    $machine_name = "edit-" . $key . "--wrapper";
    $machine_name = str_replace("_", "-", $machine_name);

    $errorTest = array_key_exists('#error_test', $element) && strtolower($element['#error_test']) == "1" ? 1 : 0;
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['error_test'] = $errorTest;

    $addressType = array_key_exists('#address_type', $element) && strtolower($element['#address_type']) == "any";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['address_type'] = $addressType;

    $addressSuggest = array_key_exists('#address_suggest', $element) && strtolower($element['#address_suggest']) == "0" ? false : true;
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['address_suggest'] = $addressSuggest;

    $verifyButtonText = array_key_exists('#verify_button_text', $element) ? $element['#verify_button_text'] : "Verify";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['verify_button_text'] = $verifyButtonText;

    $lookupTaxlot = array_key_exists('#lookup_taxlot', $element) && strtolower($element['#lookup_taxlot']) == 1;
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['lookup_taxlot'] = $lookupTaxlot;

    $showMailingLabel = array_key_exists('#show_mailing_label', $element) && strtolower($element['#show_mailing_label']) == "0";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['show_mailing_label'] = $showMailingLabel;

    $findUnincorporated = array_key_exists('#find_unincorporated', $element) && strtolower($element['#find_unincorporated']) == "1";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['find_unincorporated'] = $findUnincorporated;

    $secondaryQueryUrl = array_key_exists('#secondary_query_url', $element) ? $element['#secondary_query_url'] : false;
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['secondary_query_url'] = $secondaryQueryUrl;

    $secondaryQueryCaptureProperty = array_key_exists('#secondary_query_capture_property', $element) ? $element['#secondary_query_capture_property'] : false;
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['secondary_query_capture_property'] = $secondaryQueryCaptureProperty;

    $secondaryQueryCaptureField = array_key_exists('#secondary_query_capture_field', $element) ? $element['#secondary_query_capture_field'] : false;
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['secondary_query_capture_field'] = $secondaryQueryCaptureField;

    $notVerifiedHeading = array_key_exists('#not_verified_heading', $element) ? $element['#not_verified_heading'] : "We're unable to verify this address.";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['not_verified_heading'] = $notVerifiedHeading;

    $notVerifiedReasons = array_key_exists('#not_verified_reasons', $element) ? $element['#not_verified_reasons'] : "This sometimes happens with new addresses, PO boxes, and multi-family buildings with unit numbers.";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['not_verified_reasons'] = $notVerifiedReasons;

    $notVerifiedRemedy = array_key_exists('#not_verified_remedy', $element) ? $element['#not_verified_remedy'] : "If you're certain the address is correct, you may use it without verification.";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['not_verified_remedy'] = $notVerifiedRemedy;

    $notVerifiedRemedyRequired = array_key_exists('#not_verified_remedy_required', $element) ? $element['#not_verified_remedy_required'] : "A verified address is required. Please try again.";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['not_verified_remedy_required'] = $notVerifiedRemedyRequired;

    $requirePortlandCityLimits = array_key_exists('#require_portland_city_limits', $element) && strtolower($element['#require_portland_city_limits']) == "1";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['require_portland_city_limits'] = $requirePortlandCityLimits;

    $outOfBoundsMessage = array_key_exists('#out_of_bounds_message', $element) ? $element['#out_of_bounds_message'] : "The address you provided is outside of the Portland city limits. Please try a different address.";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['out_of_bounds_message'] = $outOfBoundsMessage;

    $secondaryQueries = array_key_exists('#secondary_queries', $element) ? $element['#secondary_queries'] : false;
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name]['secondary_queries'] = $secondaryQueries;


  }

}
