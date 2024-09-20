<?php

namespace Drupal\portland_webforms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'portland_ajax_indicator'.
 *
 * Webform composites contain a group of sub-elements. This composite implements an ajax progress indicator
 * on for any ajax call within a webform. It just need to be added somewhere on the form in a spot that's visible.
 *
 * @FormElement("portland_ajax_indicator")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 */
class PortlandAjaxIndicator extends WebformCompositeBase
{
  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element)
  {
    $element['ajax_indicator'] = [
      '#type' => 'markup',
      '#title' => 'Ajax Progress Indicator',
      '#title_display' => 'invisible',
      '#markup' => '<div id="ajax-status"><img src="/modules/custom/portland/modules/portland_webforms/images/loading.png" alt="Loading..."></div>',
    ];
    return $element;
  }
}
