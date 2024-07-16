<?php

namespace Drupal\portland_leaflet_maps\EventSubscriber;

use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api_solr\Event\PreQueryEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchApiEventSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => 'searchApiPreExecute',
      SearchApiSolrEvents::PRE_QUERY => 'solrPreQuery',
    ];
  }

  /**
   * Filter the Leaflet Map's attachment query to match the page facet query, so results match the map.
   */
  public function searchApiPreExecute(QueryPreExecuteEvent $event): void {
    $query = $event->getQuery();
    if ($query->getIndex()->getServerInstance()->supportsFeature('search_api_facets')) {
      /** @var \Drupal\facets\FacetManager\DefaultFacetManager $facet_manager */
      $facet_manager = \Drupal::service('facets.manager');

      $search_id = $query->getSearchId();

      // If we find an attachment view query, we use the same query alter as the page because they belong together
      if (str_contains($search_id, 'views_attachment:park_finder__attachment_1')) {
        $search_id = 'search_api:views_page__park_finder__page_1';

        // Add the active filters.
        $facet_manager->alterQuery($query, $search_id);
      } else if (str_contains($search_id, 'views_attachment:construction_map__')) {
        $search_id = 'search_api:views_page__construction_map__page_1';

        // Add the active filters.
        $facet_manager->alterQuery($query, $search_id);
      }
    }
  }

  /**
   * Alter the Solr query to add filter query for bounding box if `bbox` is set in the query string.
   */
  public function solrPreQuery(PreQueryEvent $event): void {
    if (isset($_GET['bbox'])) {
      $solarium_query = $event->getSolariumQuery();
      $helper = $solarium_query->getHelper();
      // Escape query parameter and then get the left, top, right and bottom coords.
      list($bottom, $left, $top, $right) = explode(',', $helper->escapeTerm($_GET['bbox']));
      // Add it to the query.
      $solarium_query->addParam('fq', "locs_field_geolocation:[$bottom,$left TO $top,$right]");
    }
  }
}
