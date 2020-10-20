<?php

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupContent;

/**
 * Implements hook_token_info().
 *
 * @ingroup portland
 */
function portland_token_info()
{
  $info = [];

  $info['types']['server'] = [
    'name' => t('Server vars'),
    'description' => t('Tokens from _SERVER.'),
  ];

  $info['tokens']['server']['HTTP_REFERER'] = [
    'name' => t('HTTP_REFERER'),
    'description' => t("Referrer header value from _SERVER"),
  ];

  $info['tokens']['server']['HTTP_USER_AGENT'] = [
    'name' => t('HTTP_USER_AGENT'),
    'description' => t("User-agent header value from _SERVER"),
  ];

  $info['types']['portland'] = [
    'name' => t('Portland'),
    'description' => t('Custom tokens from Portland module'),
  ];

  $info['tokens']['portland']['title'] = [
    'name' => t('Portland title'),
    'description' => 'A token to display standardized page titles for Portland.gov',
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 *
 * @ingroup portland
 */
function portland_tokens($type, $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata)
{
  $replacements = [];
  $sanitize = !empty($options['sanitize']);

  // Text format tokens.
  if ($type == 'server') {
    foreach ($tokens as $name => $original) {
      $replacements[$original] = $_SERVER[$name];
      if ($sanitize) {
        $replacements[$original] = filter_xss($replacements[$original]);
      }
    }
  }

  if ($type == 'portland') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'title':
          $route_match = \Drupal::routeMatch();
          $request = \Drupal::request();
          if ($route = \Drupal::routeMatch()->getCurrentRouteMatch()->getRouteObject()) {
            $title = $route->getDefault('_title');
            if (isset($title)) {
              if (is_array($title)) {
                // $title may be dynamic
                $title = $title['#markup'];
              }
              $new_title = portland_generate_extended_title_for_group_views($title, $route_match);
              $replacements[$original] = $new_title;
            }
          }
          break;
      }
    }
  }

  return $replacements;
}
