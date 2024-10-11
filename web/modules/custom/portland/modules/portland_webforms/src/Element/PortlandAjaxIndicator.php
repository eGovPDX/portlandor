<?php

namespace Drupal\portland_webforms\Element;

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
      '#markup' => <<<EOD
      <div id="ajax-overlay" aria-live="assertive" aria-hidden="true" aria-labelledby="loading-message" role="status">
        <div class="card bg-primary border-0 text-white shadow">
          <div class="card-body d-flex align-items-center">
            <div class="spinner-border"></div>
            <span id="loading-message" class="ms-4">Loading...</span>
          </div>
        </div>
      </div>
      EOD
    ];
    $element['#attached']['library'][] = 'portland_webforms/portland_ajax_indicator';

    return $element;
  }
}
