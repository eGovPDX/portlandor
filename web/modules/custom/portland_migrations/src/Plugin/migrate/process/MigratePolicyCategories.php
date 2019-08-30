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

    // $value is an array
    // 0 - 3rd level category name
    // 1 - Policy Number, from which to parse the 2nd level category abbreviations
    $l3_category = $value[0];
    $policy_number = $value[1];
    // if policy number is empty, this may be one of the overview documents; skip assigning a category
    if (!$policy_number) {
      $message = "No policy number provided; this must be an overview page (" . $l3_category . ").";
      \Drupal::logger('portland_migrations')->notice($message);
      throw new MigrateSkipProcessException();      
    }
    $policy_number_array = preg_split("/-/", $policy_number);
    // there are a few isolated cases where the policy number uses a space instead of hyphen,
    // try to capture and correct them.
    if (count($policy_number_array) < 3) {
      $policy_number = preg_replace("/ /", "-", $policy_number);
      $policy_number_array = preg_split("/-/", $policy_number);
    }
    $l2_category_code = $policy_number_array[1];
    $vocabulary = "policy_category";

    // see if l3 category exists
    $term = $this->getTermByFieldValue($vocabulary, 'name', $l3_category);
    if ($term && count($term) == 1) {
      // term exists, just return the tid to link it
      if (!is_array($term)) {
        $halt = true;
      }
      return reset($term)->id();
    } else if (count($term) > 1) {
      // more than one matching category found, whoops! log error.
      $message = "Multiple matching categories found for " . $l3_category . " in vocabulary ". $vocabulary;
      \Drupal::logger('portland_migrations')->notice($message);
      throw new MigrateSkipProcessException();      
    }

    // l3 category doesn't exist, create it
    $term = Term::create([
      'name' => $l3_category, 
      'vid'  => $vocabulary,
    ]);
    //$term = $this->getTermByFieldValue($vocabulary, 'name', $l3_category);
    //$term = reset($term);

    // get parent term by abbreviation and set it as the parent
    $parent_term = $this->getTermByFieldValue($vocabulary, 'field_category_abbreviation', $l2_category_code);
    if ($parent_term === false || count($parent_term) < 1) {
      // parent not found, throw error
      $message = "Parent term (" . $l2_category_code . ") not found for policies category term " . $l3_category . ". Cannot continue.";
      \Drupal::logger('portland_migrations')->notice($message);
      throw new MigrateException($message);
      echo $message;
      exit();
    } else if (count($parent_term) > 1) {
      // more than one parent category found with that code, throw error
      $message = "Multiple matching parent categories with code " . $l2_category_code . " found in vocabulary " . $vocabulary . ". Cannot continue.";
      \Drupal::logger('portland_migrations')->notice($message);
      throw new MigrateException();      
    }

    if (!is_array($parent_term)) {
      $halt = true;
    }
    $parent_term = reset($parent_term);
    $parent_term_id = $parent_term->id();
    $term->parent = $parent_term_id;
    $term->save();

    // return id of newly created term
    return $term->id();
  }

  /**
   * Load term by field value.
   */
  protected function getTermByFieldValue($vid, $field_name, $value) {
    $properties = [];
    $properties['vid'] = $vid;
    $properties[$field_name] = $value;
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    return $terms;
  }

}