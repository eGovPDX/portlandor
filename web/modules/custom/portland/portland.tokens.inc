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

  $info['tokens']['portland']['group_name'] = [
    'name' => t('Group name'),
    'description' => 'The group name of the current page',
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 *
 * @ingroup portland
 */
function portland_tokens($type, $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata = null)
{
  $replacements = [];
  $sanitize = !empty($options['sanitize']);

  // Text format tokens.
  if ($type == 'server') {
    foreach ($tokens as $name => $original) {
      if (array_key_exists($name, $_SERVER)) {
        $replacements[$original] = $_SERVER[$name];
        if ($sanitize) {
          $replacements[$original] = filter_xss($replacements[$original]);
        }
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
        case 'group_name':
          $route_match = \Drupal::routeMatch();

          // get the view id from the path
          $view_id = $route_match->getParameter('view_id');
          $display_id = $route_match->getParameter('display_id');

          $path = $route_match->getRouteObject()->getPath();
          // If this is a group path, get the group ID from the path
          if ($path != null && (substr_compare($path, "/group", 0, strlen("/group")) === 0)) {
            $group_id = $route_match->getParameter('group') ?? $route_match->getParameter('arg_0'); // sometimes a named group parameter is used in the path, othertimes it's a generic parameter
          } elseif ($route_match->getParameter('view')) {
            // When editing a view and previewing results, the group ID contextual filter 
            // is buried in the 'view' parameter's executable args array
            $group_id = $route_match->getParameter('view')->getExecutable()->args[0];
          }
          if (isset($group_id)) {
            $group_entity = Drupal\group\Entity\Group::load($group_id);
            if (isset($group_entity)) {
              $group_title = $group_entity->label();
              $replacements[$original] = $group_title;
            }
          }
          break;
      }
    }
  }

  return $replacements;
}
