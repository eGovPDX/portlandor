<?php

namespace Drupal\portland_address_verifier\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'portland_address_verifier' element.
 *
 * @WebformElement(
 *   id = "portland_address_verifier",
 *   label = @Translation("Portland Address Verifier"),
 *   description = @Translation("Provides an element for verifying an address in Portland."),
 *   category = @Translation("Composite elements"),
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
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // this content is used as a display value for the field value, and is what is returned by the parent
    // level token, such as [webform_submission:values:location]. If more granular field sub-field values are
    // needed, such as in a handler that is sending data to an external system, the sub-field needs to be
    // specified in the token, such as [webform_submission:values:location:place_name].
    $lines = [];
    $address;

    if ($value['location_verification_status'] == 'Verified') {
      $address = $value['address_label'];
      $address = str_replace("\r\n", "<br>", $label);
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
    $address;

    if ($value['location_verification_status'] == 'Verified') {
      $address = $value['address_label'];
      $address = str_replace("<br>", "\r\n", $label);
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

    // $machine_name = "veriifed_address";
    
    // if (array_key_exists("#webform_key", $element)) {
    //   $machine_name = $element['#webform_key'];
    // }

    $addressType = array_key_exists('#address_type', $element) && strtolower($element['#address_type']) == "any";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier']['address_type'] = $addressType;

    $verifyButtonText = array_key_exists('#verify_button_text', $element) ? $element['#verify_button_text'] : "Verify";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier']['verify_button_text'] = $verifyButtonText;

    $lookupTaxlot = array_key_exists('#lookup_taxlot', $element) && strtolower($element['#lookup_taxlot']) == "0";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier']['lookup_taxlot'] = $lookupTaxlot;

    $showMailingLabel = array_key_exists('#show_mailing_label', $element) && strtolower($element['#show_mailing_label']) == "0";
    $element['#attached']['drupalSettings']['webform']['portland_address_verifier']['show_mailing_label'] = $showMailingLabel;
  }

}
