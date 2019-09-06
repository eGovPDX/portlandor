<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\media\Entity\Media;

/**
 * In the source data, the full policy number is in a 3-part format such as BCP-BHR-16.03.
 * The first segment is the policy type. The second and third segments comprise the policy
 * number that should be stored in field_policy_number.
 * 
 * The policies.csv datafile has been modified to incluee a POLICY_NUMBER column. It was
 * previously stored in SUMMARY_TEXT on POG, but that column has been manually split to
 * only include summary text. All policy number data has been moved to the new column.
 *
 * @MigrateProcessPlugin(
 *   id = "migrate_policy_number"
 * )
 */
class MigratePolicyNumber extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $array = preg_split("/-/", $value);
    $policy_number = $array[1] . '-' . $array[2];
    return $policy_number;
  }

}