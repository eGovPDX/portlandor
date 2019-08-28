<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * This plugin migrates the 3rd level categories included in the policies.csv datafile,
 * links them to their parent category based on the 2nd level category code in the policy number,
 * and stores them as an entity reference in field_policy_category (stores the tid). 
 *
 * @MigrateProcessPlugin(
 *   id = "migrate_policy_categories"
 * )
 */
class MigratePolicyCategories extends ProcessPluginBase {
  /**
   * Migrates the policy category. Creates category and sets parent if it doesn't exist. 
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
 
  }

}