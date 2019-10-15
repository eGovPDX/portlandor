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
 *   id = "migrate_park_amenity"
 * )
 */
class MigrateParkAmenity extends ProcessPluginBase {

  private $term_name_and_id = [];
  private $term_loaded = FALSE;

  /**
   * Migrates the park amenity category.
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->loadVocabularies("park_amenities_activities");

    $key = $this->processTermName($value);
    if( !array_key_exists($key, $this->term_name_and_id)) {
      echo 'Mismatch: CSV[', $value, ']';
      return [];
    }
    // return id of newly created term
    return [
      'target_id' => $this->term_name_and_id[$this->processTermName($value)],
    ];
  }

  protected function processTermName($term_name) {
    return strtolower(str_replace(["-", " ", "(", ")"], "", $term_name));
  }

  /**
   * Load term by field value.
   */
  protected function loadVocabularies($vocabulary_name) {
    if($this->term_loaded) return;
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary_name);
    foreach ($terms as $term) {
      $this->term_name_and_id[$this->processTermName($term->name)] = $term->tid;
    }

    $this->term_loaded = TRUE;
  }
}