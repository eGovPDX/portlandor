<?php

/**
 * @file
 * Builds placeholder replacement tokens for group-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function group_token_info() {
  $types['group'] = [
    'name' => t('Group'),
    'description' => t('Tokens related to individual groups.'),
    'needs-data' => 'group',
  ];

  $tokens['group']['id'] = [
    'name' => t('Group ID'),
    'description' => t('The unique ID of the group.'),
  ];

  $tokens['group']['type'] = [
    'name' => t('Group type'),
    'description' => t('The machine name of the group type.'),
  ];

  $tokens['group']['type-name'] = [
    'name' => t('Group type name'),
    'description' => t('The human-readable name of the group type.'),
  ];

  $tokens['group']['title'] = [
    'name' => t('Title'),
  ];

  $tokens['group']['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the group is in.'),
  ];

  $tokens['group']['url'] = [
    'name' => t('URL'),
    'description' => t('The URL of the group.'),
  ];

  $tokens['group']['edit-url'] = [
    'name' => t('Edit URL'),
    'description' => t("The URL of the group's edit page."),
  ];

  $tokens['group']['created'] = [
    'name' => t('Date created'),
    'type' => 'date',
  ];

  $tokens['group']['changed'] = [
    'name' => t('Date changed'),
    'description' => t('The date the group was most recently updated.'),
    'type' => 'date',
  ];

  $tokens['group']['author'] = [
    'name' => t('Author'),
    'type' => 'user',
  ];

  $types['group_content'] = [
    'name' => t('Group content'),
    'description' => t('Tokens related to the relationships between a group and its content.'),
    'needs-data' => 'group_content',
  ];

  $tokens['group_content']['id'] = [
    'name' => t('Group content ID'),
    'description' => t('The unique ID of the group content.'),
  ];

  $tokens['group_content']['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the group content is in.'),
  ];

  $tokens['group_content']['url'] = [
    'name' => t('URL'),
    'description' => t('The URL of the group content.'),
  ];

  $tokens['group_content']['edit-url'] = [
    'name' => t('Edit URL'),
    'description' => t("The URL of the group's edit page."),
  ];

  $tokens['group_content']['created'] = [
    'name' => t('Date created'),
    'type' => 'date',
  ];

  $tokens['group_content']['changed'] = [
    'name' => t('Date changed'),
    'description' => t('The date the group content was most recently updated.'),
    'type' => 'date',
  ];

  $tokens['group_content']['group'] = [
    'name' => t('Group'),
    'type' => 'group',
  ];

  $tokens['group_content']['pretty-path-key'] = [
    'name' => t('Pretty path key'),
    'description' => t('A prettier way of labeling group content of the same plugin type.'),
  ];

  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function group_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  $url_options = ['absolute' => TRUE];
  $token_service = \Drupal::token();

  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  if ($type == 'group' && !empty($data[$type])) {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $data['group'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $group->id();
          break;

        case 'type':
          $replacements[$original] = $group->bundle();
          break;

        case 'type-name':
          $replacements[$original] = $group->getGroupType()->label();
          break;

        case 'title':
          $replacements[$original] = $group->label();
          break;

        case 'langcode':
          $replacements[$original] = $group->language()->getId();
          break;

        case 'url':
          $replacements[$original] = $group->toUrl('canonical', $url_options)->toString();
          break;

        case 'edit-url':
          $replacements[$original] = $group->toUrl('edit-form', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $group->getOwner();
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($group->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($group->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    // Actual chaining of tokens handled below.
    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $group->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $group->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $group->getChangedTime()], $options, $bubbleable_metadata);
    }
  }
  elseif ($type == 'group_content' && !empty($data[$type])) {
    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = $data['group_content'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $group_content->id();
          break;

        case 'langcode':
          $replacements[$original] = $group_content->language()->getId();
          break;

        case 'url':
          $replacements[$original] = $group_content->toUrl('canonical', $url_options)->toString();
          break;

        case 'edit-url':
          $replacements[$original] = $group_content->toUrl('edit-form', $url_options)->toString();
          break;

        case 'pretty-path-key':
          $replacements[$original] = $group_content->getContentPlugin()->getPrettyPathKey();
          break;

        // Default values for the chained tokens handled below.
        case 'group':
          $group = $group_content->getGroup();
          $bubbleable_metadata->addCacheableDependency($group);
          $replacements[$original] = $group->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($group_content->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($group_content->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }

      // Actual chaining of tokens handled below.
      if ($group_tokens = $token_service->findWithPrefix($tokens, 'group')) {
        $replacements += $token_service->generate('group', $group_tokens, ['group' => $group_content->getGroup()], $options, $bubbleable_metadata);
      }

      if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
        $replacements += $token_service->generate('date', $created_tokens, ['date' => $group_content->getCreatedTime()], $options, $bubbleable_metadata);
      }

      if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
        $replacements += $token_service->generate('date', $changed_tokens, ['date' => $group_content->getChangedTime()], $options, $bubbleable_metadata);
      }
    }
  }

  return $replacements;
}
