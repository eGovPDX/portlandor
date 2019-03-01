<?php

namespace Drupal\portland_geofield\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Element\GeofieldElementBase;
use Drupal\Core\Url;

/**
 * Provides a Geofield Map form element.
 *
 * @FormElement("portland_geofield_widget")
 */
class EsriGeofieldWidget extends GeofieldElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'latlonProcess'],
      ],
      '#element_validate' => [
        [$class, 'elementValidate'],
      ],
      '#theme_wrappers' => ['fieldset'],
    ];
  }

  /**
   * Generates the Geofield Map form element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element. Note that $element must be taken by reference here, so processed
   *   child elements are taken over into $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function latLonProcess(array &$element, FormStateInterface $form_state, array &$complete_form) {

    $mapid = 'map-' . $element['#id'];

    $element['map'] = [
      '#weight' => 0,
      '#theme' => 'portland_geofield_widget',
      '#mapid' => $mapid,
    ];

    // set up the wkt editor
    $element['wkt'] = [
      '#type' => $element['#show_wkt'] == '1' ? 'textarea' : 'hidden',
      '#title' => 'Well-Known Text',
      '#description' => t('This is the geometry value that will be saved.'),
      '#default_value' => $element['#default_value'] ? $element['#default_value'][0]['wkt'] : NULL,
      '#attributes' => [
        'id' => $element['#id'] . '-wkt',
      ],
    ];

    $element['#attached']['drupalSettings'] = [
      'portland_geofield_map' => [
        $mapid => [
          'id' => $element['#id'],
          'name' => $element['#name'],
          'basemap' => $element['#basemap'],
          'width' => isset($element['#dimensions']['width']) ? $element['#dimensions']['width'] : '100%',
          'height' => isset($element['#dimensions']['height']) ? $element['#dimensions']['height'] : '30vh',
          'value' => $element['#default_value'],
          'center' => [
            'lat' => floatval($element['#center']['lat']),
            'lon' => floatval($element['#center']['lon']),
          ],
          'zoom' => [
            'start' => intval($element['#zoom']['start']),
            'focus' => intval($element['#zoom']['focus']),
            'min' => intval($element['#zoom']['min']),
            'max' => intval($element['#zoom']['max']),
          ],
          'wktid' => $element['#id'] . '-wkt',
          'addressField' => $element['#address_field']['field'],
          'mapid' => $mapid,
        ],
      ],
    ];

    // Attach Geofield Map Libraries.
    $element['#attached']['library'][] = 'portland_geofield/widget';

    return $element;
  }

}
