<?php

namespace Drupal\portland_maps_provider\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides an PortlandMaps geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "portlandmaps",
 *   name = "PortlandMaps",
 *   handler = "\Drupal\portland_maps_provider\Geocoder\Provider\PortlandMaps",
 *   arguments = {
 *     "sourcecountry" = NULL,
 *     "usessl" = false
 *   }
 * )
 */
class PortlandMaps extends ProviderUsingHandlerWithAdapterBase {}
