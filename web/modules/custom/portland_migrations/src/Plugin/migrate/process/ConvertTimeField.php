<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * The ConvertTimeField plugin takes a time value and converts it into Drupal's internal dataformat.
 * Specifically, it converts a value like "14:35" into the number of seconds since midnight.
 * 
 * Usage:
 *   Chain it with the format_date plugin if necessary to convert a datetime value into 'H:i' format
 * 
 *   field_name:
 *     -
 *       plugin: format_date
 *       from_format: 'Y-m-d H:i:s T'
 *       to_format: 'H:i'
 *       to_timezone: 'America/Los_Angeles'
 *       source: DATE_FIELD
 *     -
 *       plugin: convert_time_field
 *
 *
 * @MigrateProcessPlugin(
 *   id = "convert_time_field"
 * )
 */
class ConvertTimeField extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    sscanf($value, "%d:%d", $hours, $minutes);
    $value = $hours * 3600 + $minutes * 60;

    return $value;
  }

}