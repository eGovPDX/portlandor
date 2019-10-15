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

    // $value is a "|" separated string of all amenities
    // "horseshoe pit|volleyball court|picnic table|paths - unpaved|playground|paths - paved|accessible play area|splash pad"
    $array = explode ( "|", $value);
    $result = [];
    foreach($array as $term_name){
      $result[] = $this->term_name_and_id[$this->processTermName($term_name)];
    }

    if(count($result) == 0)
      echo "Missing term: ".$value;
    return $result;
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