<?php

namespace Drupal\portland_zendesk\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'portland_info_block_widget' element.
 *
 * @WebformElement(
 *   id = "portland_info_block_widget",
 *   label = @Translation("Portland info block widget"),
 *   description = @Translation("Provides a markup element used to provide additional information or warnings to webform users."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\portland_zendesk\Element\PortlandInfoBlockWidget
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class PortlandInfoBlockWidget extends WebformCompositeBase {

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
    $line = "<h2>Customer Service Details</h2>";
    if (isset($value['employee_email']) && $value['employee_email']) {
      $line .= 'Form submitted by: ' . $value['employee_email'];
    }
    if (isset($value['zendesk_request_number']) && $value['zendesk_request_number']) {
      $line .= 'Zendesk request number: ' . $value['zendesk_request_number'];
    }
    if (isset($value['employee_notes_panel']['employee_notes']) && $value['employee_notes_panel']['employee_notes']) {
      $line .= 'Employee notes: ' . $value['employee_notes_panel']['employee_notes'];
    }
    $lines[] = $line;
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
      $lines[] = 'Form submitted by: ' . $value['employee_email'];
    }
    if (isset($value['zendesk_request_number']) && $value['zendesk_request_number']) {
      $lines[] = 'Zendesk request number: ' . $value['zendesk_request_number'];
    }
    if (isset($value['employee_notes_panel']['employee_notes']) && $value['employee_notes_panel']['employee_notes']) {
      $line .= 'Employee notes: ' . $value['employee_notes_panel']['employee_notes'];
    }
    return $lines;
  }

}
