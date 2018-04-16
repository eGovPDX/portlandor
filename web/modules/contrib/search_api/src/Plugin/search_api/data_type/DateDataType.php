<?php

namespace Drupal\search_api\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides a date data type.
 *
 * @SearchApiDataType(
 *   id = "date",
 *   label = @Translation("Date"),
 *   description = @Translation("Represents points in time."),
 *   default = "true"
 * )
 */
class DateDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    if ((string) $value === '') {
      return NULL;
    }
    if (is_numeric($value)) {
      return (int) $value;
    }
    $timezone = new \DateTimezone('UTC');
    $date = new \Datetime($value, $timezone);
    return $date->getTimestamp();
  }

}
