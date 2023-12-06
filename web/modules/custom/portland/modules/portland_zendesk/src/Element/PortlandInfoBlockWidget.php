<?php

namespace Drupal\portland_zendesk\Element;

use Drupal\webform\Element\WebformCompositeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'portland_info_block_widget' element.
 *
 * @FormElement("portland_info_block_widget")
 */
class PortlandInfoBlockWidget extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#process' => ['::process'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    parent::processElement($element, $form_state, $complete_form);
    // Add any additional processing here.

    // Add title textfield.
    $element['title'] = [
      '#type' => 'textfield',
      '#title' => t('Info Title'),
      '#required' => TRUE,
    ];

    // Add textarea for rich text editing.
    $element['body'] = [
      '#type' => 'text_format',
      '#title' => t('Info Body'),
      '#required' => TRUE,
      '#format' => 'full_html', // Adjust the text format as needed.
    ];

    // Add select list for style options.
    $element['style'] = [
      '#type' => 'select',
      '#title' => t('Info Style'),
      '#options' => [
        'option1' => t('Option 1'),
        'option2' => t('Option 2'),
        'option3' => t('Option 3'),
      ],
    ];

    return $element;
  }
}
