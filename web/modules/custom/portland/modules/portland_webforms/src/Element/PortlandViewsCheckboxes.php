<?php

namespace Drupal\portland_webforms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\portland_webforms\Plugin\WebformElement\PortlandViewsCheckboxesTrait;

/**
 * Provides a Drupal form element for the portland_views_checkboxes webform element plugin.
 */
#[FormElement('portland_views_checkboxes')]
class PortlandViewsCheckboxes extends Checkboxes {
  use PortlandViewsCheckboxesTrait;

  /**
   * {@inheritdoc}
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    static::setOptions($element);
    $element = parent::processCheckboxes($element, $form_state, $complete_form);

    // Must convert this element['#type'] to 'checkboxes' to prevent
    // "Illegal choice %choice in %name element" validation error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $element['#type'] = 'checkboxes';

    // Add class.
    $element['#attributes']['class'][] = 'portland-views-checkboxes';

    return $element;
  }

}
