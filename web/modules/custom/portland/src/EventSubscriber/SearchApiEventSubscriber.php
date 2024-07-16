<?php

namespace Drupal\portland\EventSubscriber;

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
   *
   */
  public function searchApiPreExecute(QueryPreExecuteEvent $event): void {

  }

  /**
   *
   */
  public function solrPreQuery(PreQueryEvent $event): void {

  }
}
