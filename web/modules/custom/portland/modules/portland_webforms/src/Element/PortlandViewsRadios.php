<?php

namespace Drupal\portland_webforms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Radios;
use Drupal\portland_webforms\Plugin\WebformElement\PortlandViewsRadiosTrait;

/**
 * Provides a Drupal form element for the portland_views_radios webform element plugin.
 */
#[FormElement('portland_views_radios')]
class PortlandViewsRadios extends Radios {
  use PortlandViewsRadiosTrait;

  /**
   * {@inheritdoc}
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    static::setOptions($element);
    $element = parent::processRadios($element, $form_state, $complete_form);

    // Must convert this element['#type'] to 'radios' to prevent
    // "Illegal choice %choice in %name element" validation error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $element['#type'] = 'radios';

    // Add class.
    $element['#attributes']['class'][] = 'portland-views-radios';

    return $element;
  }

}
