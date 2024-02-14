<?php

namespace Drupal\portland_zendesk\Element;

use Drupal\webform\Element\FormElement;

/**
 * Custom Portland Info Block element.
 *
 * @FormElement("portland_info_block")
 */
class PortlandInfoBlock extends FormElement {

  public function getInfo() {
    return [
      '#label' => t('Portland Info Block'),
      '#description' => t('Display a block of info with customizable text and background color'),
      '#process' => [],
      '#theme' => 'portland_info_block_element',
    ];
  }

  public function getElementRenderer($delta) {
    // Get configuration values
    $title = $this->getValue($delta)['title'];
    $warning_level = $this->getValue($delta)['warning_level'];
    $text = $this->getValue($delta)['message_text'];
  
    // Return data for the template
    return [
      '#title' => $title,
      '#warning_level' => $warning_level,
      '#message_text' => $text,
    ];
  }
    
  public function getSubmissionForm($delta) {
    $form = [];
  
    // Title field
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title (max 100 characters)'),
      '#default_value' => $this->getValue($delta)['title'],
      '#maxlength' => 100,
      '#element_validate' => [
        [
          '\Drupal\portland_zendesk\Plugin\Element\PortlandInfoBlock::validateTitleLength',
          100,
        ],
      ],
    ];
  
    // Message Text field
    $form['message_text'] = [
      '#type' => 'textarea',
      '#title' => t('Message Text'),
      '#default_value' => $this->getValue($delta)['message_text'],
      '#required' => TRUE,
    ];
  
    // Warning Level select
    $form['warning_level'] = [
      '#type' => 'select',
      '#title' => t('Warning Level'),
      '#options' => [
        'info' => t('Information'),
        'warning' => t('Warning'),
      ],
      '#default_value' => $this->getValue($delta)['warning_level'],
    ];
  
    // Process submitted data
    $form['#submit'] = [$this, 'submitConfiguration'];
  
    return $form;
  }
  
  public function validateTitleLength($element, $length, $form_state) {
    if (strlen($element['#value']) > $length) {
      $form_state->setErrorByName('title', t('The title cannot exceed @length characters.', ['@length' => $length]));
    }
  }
  
  public function submitConfiguration($form, $form_state) {
    $values = $form_state->getValues();
    $this->setValue($delta, $values); // Update element configuration
    return $form; // Optional form rebuild in case of errors
  }
    
}
