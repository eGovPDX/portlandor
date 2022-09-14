<?php

namespace Drupal\portland_zendesk\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'portland_support_agent_widget' element.
 *
 * Composite element contains a set of sub-elements that generate a Customer Service Use Only
 * block on webforms that might be completed on behalf of community members.
 * 
 * Employee Email (textfield, default = logged-in user name & email)
 * Zendesk Request Number (textfield)
 * Test Submission (checkbox)
 
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("portland_support_agent_widget")
 */
class PortlandSupportAgentWidget extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   * NOTE: custom elements must have a #title attribute. if a value is not set here, it must be set
   * in the field config. if not, an error is thrown when trying to add an email handler.
   * 
   * How to programmatically set field conditions: https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
   */
  public static function getCompositeElements(array $element) {

    $currentUser = \Drupal::currentUser();
    $currentUserEmail = $currentUser->getEmail();
    $currentUserName = $currentUser->getDisplayName();

    $elements = [];
    $elements['support_agent_widget'] = [
      '#id' => 'support_agent_widget',
      '#type' => 'container',
      '#attributes' => ['style' => ['padding: 15px; background-color: #cccccc;']],
    ];
    $elements['support_agent_widget']['title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>Customer Service Use Only</h2>',
    ];
    $elements['support_agent_widget']['employee_email'] = [
      '#type' => 'textfield',
      '#title' => t('Employee Email'),
      '#id' => 'employee_email',
      '#value' => $currentUserName . ' <[' . $currentUserEmail . ']>',
    ];
    $elements['support_agent_widget']['zendesk_request_number'] = [
      '#type' => 'number',
      '#title' => t('Zendesk Request Number'),
      '#id' => 'zendesk_request_number',
      '#description' => 'If you are completing this webform on behalf of a community member, please enter the Zendesk request number of the request created to track the interaction. In addition to creating a new request for this report, the existing interaction request will be updated and linked.',
    ];
    $elements['support_agent_widget']['test_submission'] = [
      '#type' => 'checkbox',
      '#title' => t('Test Submission'),
      '#id' => 'test_submission',
    ];

    return $elements;
  }
}
