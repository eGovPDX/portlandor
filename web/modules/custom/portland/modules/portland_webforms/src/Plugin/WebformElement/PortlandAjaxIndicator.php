<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides a 'portland_ajax_indicator' element.
 *
 * @WebformElement(
 *   id = "portland_ajax_indicator",
 *   label = @Translation("Portland Ajax Indicator"),
 *   description = @Translation("Provides an element that displays a progress spinner whenever an ajax operation occurs in a webform."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\portland_address_verifier\Element\PortlandAddressVerifier
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class PortlandAjaxIndicator extends WebformCompositeBase {


}
