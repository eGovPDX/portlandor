<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Migrates the level-2 city policy categories and skips if they already exist in the vocabulary.
 * The level-3 categories are migrated as part of the main policies migration.
 *
 * @MigrateProcessPlugin(
 *   id = "migrate_l2_categories"
 * )
 */
class MigrateL2Categories extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($term_name, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $vocabulary = "policies_categories";

    if (empty($term_name)) {
      throw new MigrateSkipProcessException();
    }

    if ($tid = $this->getTidByName($term_name, $vocabulary)) {
      $term = Term::load($tid);
      throw new MigrateSkipProcessException();
    }
    else {
      $term = Term::create([
        'name' => $term_name, 
        'vid'  => $vocabulary,
      ])->save();
      if ($tid = $this->getTidByName($term_name, $vocabulary)) {
        $term = Term::load($tid);
      }
    }
    return $term_name;
  }

  /**
   * Load term by name.
   */
  protected function getTidByName($name = NULL, $vocabulary = NULL) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
    }
    if (!empty($vocabulary)) {
      $properties['vid'] = $vocabulary;
    }
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    return !empty($term) ? $term->id() : 0;
  }
}