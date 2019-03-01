<?php

namespace Drupal\portland_geofield;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EsriGeofieldViewTrait.
 *
 * Provide common functions for Geofield Map fields.
 *
 * @package Drupal\portland_geofield
 */
trait EsriGeofieldViewTrait {
  /**
   * Get the Default Settings.
   *
   * @return array
   *   The default settings.
   */
  public static function getDefaultSettings() {
    return [
      'dimensions' => [
        'width' => '100%',
        'height' => '30vh',
      ],
      'center' => [
        'lat' => '45.523452',
        'lon' => '-122.676207',
      ],
    ];
  }

  function getViewJsonData($geofield_value, $description, $view_data) {
    $data = [];

    foreach($geofield_value as $value) {
      $data[] = [
        'wkt' => $value,
        'popup' => implode('', $view_Data),
      ];
    }

    return $data;
  }
}
