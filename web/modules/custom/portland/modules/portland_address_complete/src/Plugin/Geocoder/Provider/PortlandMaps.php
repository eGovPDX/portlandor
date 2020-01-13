<?php

namespace Drupal\portland_address_complete\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides an PortlandMaps geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "portlandmaps",
 *   name = "PortlandMaps",
 *   handler = "\Drupal\portland_address_complete\Geocoder\Provider\PortlandMaps",
 *   arguments = {
 *     "sourcecountry" = NULL,
 *     "usessl" = false
 *   }
 * )
 */
class PortlandMaps extends ProviderUsingHandlerWithAdapterBase {}
