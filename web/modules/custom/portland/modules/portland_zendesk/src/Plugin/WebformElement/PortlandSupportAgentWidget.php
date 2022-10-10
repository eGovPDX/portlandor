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
    return $lines;
  }

}
