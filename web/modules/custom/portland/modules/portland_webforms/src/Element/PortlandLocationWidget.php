<?php

namespace Drupal\portland_webforms\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'portland_location_widget'.
 *
 * Webform composites contain a group of sub-elements.
 *
 * @FormElement("portland_location_widget")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\portland_webforms\Element\PortlandLocationWidget
 */
class PortlandLocationWidget extends WebformCompositeBase
{
  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element)
  {
    $element_id = $element['#webform_key'];
    
    $element['location_search'] = [
      '#type' => 'textfield',
      '#title' => t('Location Search'),
      '#id' => 'location_search',
      '#attributes' => ['class' => ['location-widget-address'], 'autocomplete' => 'off'],
      '#description' => t('Search the map for an address, cross streets, park, or community center. Or use the map to click a location.'),
      '#description_display' => 'before',
    ];
    $element['precision_text'] = [
      '#type' => 'markup',
      '#title' => 'Precision',
      '#title_display' => 'invisible',
      '#markup' => '<div class="alert alert--info next-steps visually-hidden precision_text" aria-hidden="true" id="precision_text"><strong>IMPORTANT:</strong> To help us provide better service, please click, tap, or drag the marker to the precise location on the map.</div>',
    ];
    $element['location_map'] = [
      '#type' => 'markup',
      '#id' => 'location_map',
      '#title' => 'Location map',
      '#description' => '',
      '#description_display' => 'before',
      '#title_display' => 'invisible',
      '#markup' => '<div id="' . $element_id . '_map_container" class="location-map"></div><div class="loader-container" role="status"><div class="loader"></div><div class="visually-hidden">Loading...</div></div>',
    ];

    return $element;
  }
}
