<?php

namespace Drupal\facets\Plugin\facets\query_type;

use Drupal\facets\QueryType\QueryTypeRangeBase;

/**
 * Basic support for numeric facets grouping by a granularity value.
 *
 * Requires the facet widget to set configuration value keyed with
 * granularity.
 *
 * @FacetsQueryType(
 *   id = "search_api_granular",
 *   label = @Translation("Numeric query with set granularity"),
 * )
 */
class SearchApiGranular extends QueryTypeRangeBase {

  /**
   * {@inheritdoc}
   */
  public function calculateRange($value) {
    return [
      'start' => $value,
      'stop' => (int) $value + $this->getGranularity(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateResultFilter($value) {
    assert($this->getGranularity() > 0);
    return [
      'display' => $value - ($value % $this->getGranularity()),
      'raw' => $value - ($value % $this->getGranularity()) ,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFacetOptions() {
    return parent::getFacetOptions() + [
      'granularity' => $this->getGranularity(),
    ];
  }

  /**
   * Looks at the configuration for this facet to determine the granularity.
   *
   * Default behaviour an integer for the steps that the facet works in.
   *
   * @return int
   *   If not an integer the inheriting class needs to deal with calculations.
   */
  protected function getGranularity() {
    return $this->facet->getProcessors()['granularity_item']->getConfiguration()['granularity'];
  }

}
