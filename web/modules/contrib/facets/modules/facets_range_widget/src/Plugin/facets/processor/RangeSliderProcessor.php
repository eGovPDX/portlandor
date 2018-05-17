<?php

namespace Drupal\facets_range_widget\Plugin\facets\processor;

use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;

/**
 * Provides a processor that adds all range values between an min and max range.
 *
 * @FacetsProcessor(
 *   id = "range_slider",
 *   label = @Translation("Range slider"),
 *   description = @Translation("Add range results for all the steps between min and max range."),
 *   stages = {
 *     "pre_query" = 5,
 *     "post_query" = 5,
 *     "build" = 5
 *   }
 * )
 */
class RangeSliderProcessor extends SliderProcessor implements PreQueryProcessorInterface, BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet) {
    $active_items = $facet->getActiveItems();

    array_walk($active_items, function (&$item) {
      if (preg_match('/\(min:((?:-)?[\d\.]+),max:((?:-)?[\d\.]+)\)/i', $item, $matches)) {
        $item = [$matches[1], $matches[2]];
      }
      else {
        $item = NULL;
      }
    });
    $facet->setActiveItems($active_items);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    /** @var \Drupal\facets\Plugin\facets\processor\UrlProcessorHandler $url_processor_handler */
    $url_processor_handler = $facet->getProcessors()['url_processor_handler'];
    $url_processor = $url_processor_handler->getProcessor();
    $filter_key = $url_processor->getFilterKey();

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    foreach ($results as &$result) {
      $url = $result->getUrl();
      $query = $url->getOption('query');

      // Remove all the query filters for the field of the facet.
      if ($query !== NULL) {
        foreach ($query[$filter_key] as $id => $filter) {
          if (strpos($filter . $url_processor->getSeparator(), $facet->getUrlAlias()) === 0) {
            unset($query[$filter_key][$id]);
          }
        }
      }

      // Add one generic query filter with the min and max placeholder.
      $query[$filter_key][] = $facet->getUrlAlias() . $url_processor->getSeparator() . '(min:__range_slider_min__,max:__range_slider_max__)';
      $url->setOption('query', $query);
      $result->setUrl($url);
    }

    return $results;
  }

}
