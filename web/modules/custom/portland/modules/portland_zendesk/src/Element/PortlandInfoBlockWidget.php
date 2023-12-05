<?php

namespace Drupal\portland_zendesk\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'portland_info_block_widget' element.
 *
 *
 * @FormElement("portland_info_block_widget")
 */
class PortlandInfoBlockWidget extends WebformCompositeBase {

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

    $element['#title'] = ['Info Block Widget'];
    
    $form['attributes']['markup_headline'] = [
      '#type' => 'textfield',
      '#title' => t('Headline'),
      // Add more settings as needed.
    ];

    $form['attributes']['markup_body'] = [
      '#type' => 'textarea',
      '#title' => t('Body'),
      // Add more settings as needed.
    ];

    $form['attributes']['markup_style'] = [
      '#type' => 'select',
      '#title' => t('Style'),
      // Add more settings as needed.
    ];

    $form['attributes']['markup_body'] = [
      '#type' => 'text_format',
      '#title' => t('Body'),
      '#format' => 'simple_editor',
    ];


    // $element['info_block_widget_title'] = [
    //   '#type' => 'markup',
    //   '#title' => t('Support Agent Widget'),
    //   '#title_display' => 'invisible',
    //   '#markup' => '<h2>Customer Service Use Only</h2><div><strong>You are viewing this form as an employee. To submit a report as a community member, please log out.</strong></div>',
    // ];
    // $element['employee_email'] = [
    //   '#type' => 'textfield',
    //   '#title' => t('Employee Email'),
    //   '#id' => 'employee_email',
    //   '#value' => $currentUserName . ' <[' . $currentUserEmail . ']>',
    // ];
    // $element['zendesk_request_number'] = [
    //   '#type' => 'number',
    //   '#title' => t('Zendesk Request Number'),
    //   '#id' => 'zendesk_request_number',
    //   '#description' => 'If you are completing this webform on behalf of a community member, please enter the Zendesk request number of the request created to track the interaction. In addition to creating a new request for this report, the existing interaction request will be updated and linked.',
    // ];
    // $element['employee_notes_panel'] = [
    //   '#type' => 'details',
    //   '#title' => 'Employee Notes',
    //   '#format' => 'details-closed',
    // ];
    // $element['employee_notes_panel']['employee_notes'] = [
    //   '#type' => 'textarea',
    //   '#title' => 'Employee Notes',
    //   '#title_display' => 'invisible',
    //   '#description' => 'Use this area as a scratch pad or as a field to add additional notes to the request. Anything submitted in this field will be included in the request description and may be visible to the requester.'
    // ];
    // $element['escalate_issue'] = [
    //   '#type' => 'checkbox',
    //   '#title' => t('Escalate this issue'),
    //   '#id' => 'escalate_issue'
    // ];
    // $element['test_submission'] = [
    //   '#type' => 'checkbox',
    //   '#title' => t('Test Submission'),
    //   '#id' => 'test_submission',
    //   '#description' => 'For administrtor use only. Handlers can be configured to process form submissions differently based on whether this box is checked. Typically configured to place tickets in the Developer Test Group in Zendesk.',
    //   '#access_create_roles' => ['administrator'],
    //   '#access_update_roles' => ['administrator'],
    //   '#access_view_roles' => ['administrator'],
    // ];

    return $element;
  }
}
