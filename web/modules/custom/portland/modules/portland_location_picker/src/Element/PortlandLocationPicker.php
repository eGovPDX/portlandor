<?php

namespace Drupal\portland_location_picker\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'portland_location_picker'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("portland_location_picker")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\portland_location_picker\Element\PortlandLocationPicker
 */
class PortlandLocationPicker extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'portland_locatiom_picker'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['location_address'] = [
      '#type' => 'textfield',
      '#title' => t('Address or Cross Streets'),
      '#attributes' => ['class' => ['location-picker-address']],
      '#required' => TRUE,
      '#description' => t('Enter an address or cross streets, then click the Locate button to verify the location. Alternately, you may click the map to set the location.'),
    ];
    $elements['location_map'] = [
      '#type' => 'markup',
      '#markup' => '<div id="location_map" class="location-map">Map of Portland</div>',
    ];
    $elements['suggestions_modal'] = [
      '#type' => 'markup',
      '#markup' => '<div id="suggestions_modal" class="visually-hidden"></div>',
    ];
    $elements['place_name'] = [
      '#type' => 'textfield',
      '#id' => 'place_name',
      '#title' => t('Place name'),
      '#attributes' => ['class' => ['place-name']],
      '#description' => t('If this location has a name, such as a business or public building, please enter it here.')
    ];
    $elements['location_lat'] = [
      '#type' => 'hidden',
      '#id' => 'location_lat',
      '#attributes' => ['class' => ['location-lat']],
    ];
    $elements['location_lon'] = [
      '#type' => 'hidden',
      '#id' => 'location_lon',
      '#attributes' => ['class' => ['location-lon']],
    ];

    return $elements;
  }

}
