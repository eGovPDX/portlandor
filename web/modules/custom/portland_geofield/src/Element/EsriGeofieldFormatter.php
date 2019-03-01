<?php

namespace Drupal\portland_geofield\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;

/**
 * Provides a Geofield Map form element.
 *
 * @RenderElement("portland_geofield_formatter")
 */
class EsriGeofieldFormatter extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'portland_geofield_formatter',
      '#pre_render' => [
        [$this, 'preRenderEsriGeofieldFormatter'],
      ],
      '#attached' => [
        'library' => [
          'portland_geofield/formatter',
        ],
      ],
    ];
  }

  /**
   * Create the render array that should come along with this element
   */
  function preRenderEsriGeofieldFormatter(array $element) {
    // pull out the id so we can map the settings and data
    $mapid = $element['#mapid'];

    $element['map'] = array(
      '#theme' => 'portland_geofield_formatter',
      '#mapid' => $mapid,
    );

    $element['#attached']['drupalSettings'] = [
      'portland_geofield_map' => [
        $mapid => [
          'basemap' => $element['#basemap'],
          'width' => isset($element['#dimensions']['width']) ? $element['#dimensions']['width'] : '100%',
          'height' => isset($element['#dimensions']['height']) ? $element['#dimensions']['height'] : '30vh',
          'value' => $element['#value'],
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
          'mapid' => $mapid,
        ],
      ],
    ];

    return $element;
  }
}
