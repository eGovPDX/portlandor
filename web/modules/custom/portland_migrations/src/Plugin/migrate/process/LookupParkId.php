<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * The Park Amenity taxonomy terms in POG are split into three vocabularies in POWR.
 * Need to compare the text value and insert the term into the correct field in Park Facility.
 *
 * @MigrateProcessPlugin(
 *   id = "lookup_park_id"
 * )
 */
class LookupParkId extends ProcessPluginBase {

  /**
   * Migrates the park amenity category.
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['field_pog_property_id' => $value, 'type' => 'park_facility']);

    if(count($nodes) === 0) return NULL;
    // Only expect one result. The key of the first array element is the park's NID in POWR.
    return array_keys($nodes)[0];
  }
}