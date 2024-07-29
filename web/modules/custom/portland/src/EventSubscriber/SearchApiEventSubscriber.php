<?php

namespace Drupal\portland\EventSubscriber;

use Drupal\search_api_solr\Event\PreQueryEvent;
use Drupal\search_api_solr\Event\PostCreateIndexDocumentsEvent;
use Drupal\search_api_solr\Event\SearchApiSolrEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SearchApiEventSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiSolrEvents::POST_CREATE_INDEX_DOCUMENTS => 'solrPostCreateIndexDocuments',
      SearchApiSolrEvents::PRE_QUERY => 'solrPreQuery',
    ];
  }

  /**
   * Our "aggregated sorting date" field is used for date-based boosting. This event
   * will change the date field that is used depending on the content type. For instance, on events we want to use start date.
   */
  public function solrPostCreateIndexDocuments(PostCreateIndexDocumentsEvent $event): void {
    $documents = $event->getSolariumDocuments();
    foreach ($documents as $document) {
      if ($document->ss_content_type === 'news') {
        // When editor did not specify an Updated On date, use Published On date
        if ($document->ds_field_updated_on == null) {
          $document->ds_aggregated_sorting_date = $document->ds_field_published_on;
        } else {
          $document->ds_aggregated_sorting_date = $document->ds_field_updated_on;
        }
      } else if ($document->ss_content_type === 'event') {
        $document->ds_aggregated_sorting_date = $document->ds_field_start_date;
      }
    }

    $event->setSolariumDocuments($documents);
  }

  /**
   * Alter Solr query to boost relevancy score based on aggregated_sorting_date.
   */
  public function solrPreQuery(PreQueryEvent $event): void {
    $query = $event->getSearchApiQuery();
    $solarium_query = $event->getSolariumQuery();

    // Only modify the query for sitewide search
    $search_id = $query->getSearchId();
    if (!str_contains($search_id, "search_api_page:search_portland_gov")
      && !str_contains($search_id, "search_api_autocomplete:search_portland_gov")) {
      return;
    }

    $date_field_name = 'aggregated_sorting_date'; // This can be any UNIX timestamp field
    $index = $query->getIndex();
    $fields = $index->getServerInstance()->getBackend()->getSolrFieldNames($index);
    $solr_field = !empty($fields[$date_field_name]) ? $fields[$date_field_name] : '';
    // See the link above for the effect of magic numbers
    if ($solr_field) {
      // 1. Calculate the difference between NOW and the Date field in milliseconds
      //    Two weeks 8.27e-10, One week 1.65e-9, Two days 5.79e-9, One day 1.157e-8
      // 2. Use the reciprocal function y = a / (m * x + b)
      //    a. m = 8.27e-10: Convert the time difference into 2 weeks
      //    b. a = 0.15 and b = 0.1: Adjust how quickly the curve decline
      //    c. Can plot the function at https://www.desmos.com/calculator
      // 3. Add the result to query($q) which is the original Solr score.
      // https://stackoverflow.com/questions/22017616/stronger-boosting-by-date-in-solr

      // When Start Date of Event is in the future or News since the last month, give a large boost.
      // Otherwise, give small boost based on start date or updated date.
      $solarium_query->addParam('sort', 'if(or(and(eq(ss_content_type, "event"),gte(ms(ds_aggregated_sorting_date,NOW/DAY), 0)),and(eq(ss_content_type, "news"),gte(ms(ds_aggregated_sorting_date,NOW-1MONTH/DAY),0))),product(query($q), recip(abs(ms(NOW/DAY,ds_aggregated_sorting_date)),1.157e-8,6.5,0.5)),if(or(eq(ss_content_type, "event"),eq(ss_content_type, "news")),product(query($q), recip(abs(ms(NOW/DAY,ds_aggregated_sorting_date)),1.157e-8,2,0.5)),query($q))) desc');
    }
  }
}
