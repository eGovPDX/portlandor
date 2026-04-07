<?php

namespace Drupal\portland_webforms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\Select;
use Drupal\portland_webforms\Plugin\WebformElement\PortlandViewsSelectTrait;

/**
 * Provides a Drupal form element for the portland_views_select webform element plugin.
 */
#[FormElement('portland_views_select')]
class PortlandViewsSelect extends Select {
  use PortlandViewsSelectTrait;

  /**
   * {@inheritdoc}
   */
  public static function processSelect(&$element, FormStateInterface $form_state, &$complete_form) {
    static::setOptions($element);
    $element = parent::processSelect($element, $form_state, $complete_form);

    // Must convert this element['#type'] to a 'select' to prevent
    // "Illegal choice %choice in %name element" validation error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $element['#type'] = 'select';

    // Add class.
    $element['#attributes']['class'][] = 'portland-views-select';

    return $element;
  }

}
