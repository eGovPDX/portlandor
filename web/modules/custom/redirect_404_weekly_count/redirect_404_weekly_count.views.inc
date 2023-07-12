<?php

/**
 * @file
 * Provide views data for redirect_404.module.
 */

use Drupal\redirect_404\SqlRedirectNotFoundStorage;

/**
 * Implements hook_views_data().
 */
function weekly_count_redirect_404_views_data() {
    $data = [];
  
    // Only define views data if the service uses our specific implementation.
    if (!\Drupal::service('redirect.not_found_storage') instanceof SqlRedirectNotFoundStorage) {
      return $data;
    }

    $data['redirect_404']['table']['group'] = t('Redirect 404');
  
    $data['redirect_404']['weekly_count'] = [
      'title' => t('Weekly count'),
      'help' => t('The number of requests with that path and language in a week.'),
      'field' => [
        'id' => 'numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
    ];
  
    return $data;
  }
  