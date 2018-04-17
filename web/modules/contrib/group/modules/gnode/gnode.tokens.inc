<?php

/**
 * @file
 * Builds placeholder replacement tokens for group node-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupContent;

/**
 * Implements hook_token_info().
 */
function gnode_token_info() {
  $tokens = [];

  $tokens['group'] = [
    'name' => t('Group'),
    'description' => t('The parent group.'),
    'type' => 'group',
  ];

  if (\Drupal::moduleHandler()->moduleExists('token')) {
    $tokens['groups'] = [
      'name' => t('Groups'),
      'description' => t("An array of all the node parent groups."),
      'type' => 'array',
    ];
  }

  return array(
    'tokens' => array('node' => $tokens),
  );
}

/**
 * Implements hook_tokens().
 */
function gnode_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  if ($type != 'node' || empty($data['node'])) {
    return [];
  }

  $token_service = \Drupal::token();
  $replacements = [];

  $group_content_array = GroupContent::loadByEntity($data['node']);
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

  // [node:groups:*] chained tokens.
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

  return $replacements;
}
