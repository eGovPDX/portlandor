<?php

/**
 * Implements hook_token_info().
 *
 * @ingroup portland
 */
function portland_token_info() {
  $tokens = [];

  $tokens['types']['server'] = [
    'name' => t('Server vars'),
    'description' => t('Tokens from _SERVER.'),
  ];

  $tokens['tokens']['server']['HTTP_REFERER'] = [
    'name' => t('HTTP_REFERER'),
    'description' => t("Var from _SERVER"),
  ];

  $tokens['tokens']['server']['HTTP_USER_AGENT'] = [
    'name' => t('HTTP_USER_AGENT'),
    'description' => t("Var from _SERVER"),
  ];

  $tokens['tokens']['media']['group'] = [
    'name' => t('Group'),
    'description' => t('The parent group.'),
    'type' => 'group',
  ];

  if (\Drupal::moduleHandler()->moduleExists('token')) {
    $tokens['tokens']['media']['groups'] = [
      'name' => t('Groups'),
      'description' => t("An array of all the node parent groups."),
      'type' => 'array',
    ];
  }

  return $tokens;
}

/**
 * Implements hook_tokens().
 *
 * @ingroup portland
 */
function portland_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = [];
  $sanitize = !empty($options['sanitize']);

  if ($type == 'media' || !empty($data['media'])) {
    $token_service = \Drupal::token();

    $group_content_array = GroupContent::loadByEntity($data['media']);
    if (empty($group_content_array)) {
      return [];
    }

    $groups = [];
    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    foreach ($group_content_array as $group_content) {
      $group = $group_content->getGroup();
      $groups[$group->id()] = $group->label();
      $bubbleable_metadata->addCacheableDependency($group);
    };

    if (isset($tokens['groups'])) {
      $replacements[$tokens['groups']] = token_render_array($groups, $options);
    }

    // [media:groups:*] chained tokens.
    if ($parents_tokens = \Drupal::token()->findWithPrefix($tokens, 'groups')) {
      $replacements += \Drupal::token()->generate('array', $parents_tokens, array('array' => $groups), $options, $bubbleable_metadata);
    }

    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = array_pop($group_content_array);
    $group = $group_content->getGroup();
    if (isset($tokens['group'])) {
      $replacements[$tokens['group']] = $group->label();
    }

    if ($group_tokens = $token_service->findWithPrefix($tokens, 'group')) {
      $replacements += $token_service->generate('group', $group_tokens, array('group' => $group), $options, $bubbleable_metadata);
    }
  }

  // Text format tokens.
  if ($type == 'server') {

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'HTTP_REFERER':
          $replacements[$original] = $_SERVER['HTTP_REFERER'];
          if ($sanitize) {
            $replacements[$original] = filter_xss($replacements[$original]);
          }
          break;

        case 'HTTP_USER_AGENT':
          $replacements[$original] = $_SERVER['HTTP_USER_AGENT'];
          if ($sanitize) {
            $replacements[$original] = filter_xss($replacements[$original]);
          }
          break;
      }
    }
  }

  return $replacements;
}
