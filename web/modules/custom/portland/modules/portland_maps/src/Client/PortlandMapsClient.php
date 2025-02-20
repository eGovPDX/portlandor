<?php

namespace Drupal\portland_maps\Client;

/**
 * Class PortlandMapsClient
 * @package Drupal\portland_maps\Client
 */
class PortlandMapsClient {

  public function getDetail($query) {
    // Read the JSON data from the file.
    $json_file_path = DRUPAL_ROOT . '/modules/custom/portland/modules/portland_maps/data/R259621.json';
    if (file_exists($json_file_path)) {
      $json_data = file_get_contents($json_file_path);
      return json_decode($json_data, TRUE);
    } else {
      throw new \Exception('JSON file not found: ' . $json_file_path);
    }
  }
}
