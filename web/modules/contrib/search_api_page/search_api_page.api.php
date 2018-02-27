<?php

/**
 * @file
 * Hooks provided by the Search API Page module.
 */

/**
 * Alter the Search API results page.
 *
 * Modules may implement this hook to alter the search results page elements,
 * all properties from \Drupal\search_api\Query\ResultSet are available here.
 *
 * @param array $build
 *   An array containing all page elements.
 * @param \Drupal\search_api\Query\ResultSet $query_result
 *   Search API query result.
 *
 * @see \Drupal\search_api_page\Controller\SearchApiPageController
 */
function hook_search_api_page_alter(&$build, $query_result) {
  $search_title = \Drupal::translation()->translate(
    'Search results (@results)',
    array(
      '@results' => $query_result->getResultCount(),
    )
  );

  $build['#search_title'] = array(
    '#markup' => $search_title,
  );
}
