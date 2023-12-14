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
 * The class fieldset#edit-support-agent-use-only--wrapper has been added to _form.scss
 * to provide styling for this widget.
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
   * in the field config. if not, an error is thrown when trying to add an email handler (why?).
   * 
   * When configuring this element, need to set the access to be Administrator and Support Agent roles only. 
   * 
   * How to programmatically set field conditions: https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
   */
  public static function getCompositeElements(array $element) {

    $currentUser = \Drupal::currentUser();
    $currentUserEmail = $currentUser->getEmail();
    $currentUserName = $currentUser->getDisplayName();

    $element['#title'] = ['Support Agent Widget'];
    
    $element['support_agent_widget_title'] = [
      '#type' => 'markup',
      '#title' => t('Support Agent Widget'),
      '#title_display' => 'invisible',
      '#markup' => '<h2>Customer Service Use Only</h2><div><strong>You are viewing this form as an employee. To submit a report as a community member, please log out.</strong></div>',
    ];
    $element['employee_email'] = [
      '#type' => 'textfield',
      '#title' => t('Employee Email'),
      '#id' => 'employee_email',
      '#value' => $currentUserName . ' <[' . $currentUserEmail . ']>',
    ];
    $element['zendesk_request_number'] = [
      '#type' => 'number',
      '#title' => t('Zendesk Request Number'),
      '#id' => 'zendesk_request_number',
      '#description' => 'If you are completing this webform on behalf of a community member, please enter the Zendesk request number of the request created to track the interaction. In addition to creating a new request for this report, the existing interaction request will be updated and linked.',
    ];
    $element['employee_notes_panel'] = [
      '#type' => 'details',
      '#title' => 'Employee Notes',
      '#format' => 'details-closed',
    ];
    $element['employee_notes_panel']['employee_notes'] = [
      '#type' => 'textarea',
      '#title' => 'Employee Notes',
      '#title_display' => 'invisible',
      '#description' => 'Use this area as a scratch pad or as a field to add additional notes to the request. Anything submitted in this field will be included in the request description and may be visible to the requester.'
    ];
    $element['escalate_issue'] = [
      '#type' => 'checkbox',
      '#title' => t('Escalate this issue'),
      '#id' => 'escalate_issue'
    ];
    $element['test_submission'] = [
      '#type' => 'checkbox',
      '#title' => t('Test Submission'),
      '#id' => 'test_submission',
      '#description' => 'For administrator use only. Handlers can be configured to process form submissions differently based on whether this box is checked. Typically configured to place tickets in the Developer Test Group in Zendesk.',
      '#access_create_roles' => ['administrator'],
      '#access_update_roles' => ['administrator'],
      '#access_view_roles' => ['administrator'],
    ];

    return $element;
  }
}
