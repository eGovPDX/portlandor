<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Generates text using placeholders for dummy content to check group content
 * token replacement.
 *
 * @group group
 */
class GroupContentTokenReplaceTest extends GroupTokenReplaceKernelTestBase {

  /**
   * Tests the tokens replacement for group content.
   */
  function testGroupContentTokenReplacement() {
    $url_options = [
      'absolute' => TRUE,
      'language' => $this->interfaceLanguage,
    ];

    // Create a group and retrieve the group content for the owner's membership.
    $group = $this->createGroup();
    $account = $group->getOwner();
    $group_content = $group->getMember($account)->getGroupContent();

    // Generate and test tokens.
    $tests = [];
    $tests['[group_content:id]'] = $group_content->id();
    $tests['[group_content:langcode]'] = $group_content->language()->getId();
    $tests['[group_content:url]'] = $group_content->url('canonical', $url_options);
    $tests['[group_content:edit-url]'] = $group_content->url('edit-form', $url_options);
    $tests['[group_content:pretty-path-key]'] = $group_content->getContentPlugin()->getPrettyPathKey();
    $tests['[group_content:group]'] = $group->label();
    $tests['[group_content:group:id]'] = $group->id();
    $tests['[group_content:created:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($group_content->getCreatedTime(), ['langcode' => $this->interfaceLanguage->getId()]);
    $tests['[group_content:changed:since]'] = \Drupal::service('date.formatter')->formatTimeDiffSince($group_content->getChangedTime(), ['langcode' => $this->interfaceLanguage->getId()]);

    $base_bubbleable_metadata = BubbleableMetadata::createFromObject($group_content);

    $metadata_tests = [];
    $metadata_tests['[group_content:id]'] = $base_bubbleable_metadata;
    $metadata_tests['[group_content:langcode]'] = $base_bubbleable_metadata;
    $metadata_tests['[group_content:url]'] = $base_bubbleable_metadata;
    $metadata_tests['[group_content:edit-url]'] = $base_bubbleable_metadata;
    $metadata_tests['[group_content:pretty-path-key]'] = $base_bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[group_content:group]'] = $bubbleable_metadata->addCacheTags($group->getCacheTags());
    $metadata_tests['[group_content:group:id]'] = $bubbleable_metadata;
    $bubbleable_metadata = clone $base_bubbleable_metadata;
    $metadata_tests['[group_content:created:since]'] = $bubbleable_metadata->setCacheMaxAge(0);
    $metadata_tests['[group_content:changed:since]'] = $bubbleable_metadata;

    // Test to make sure that we generated something for each token.
    $this->assertFalse(in_array(0, array_map('strlen', $tests)), 'No empty tokens generated.');

    foreach ($tests as $token => $expected) {
      $bubbleable_metadata = new BubbleableMetadata();
      $output = $this->tokenService->replace($token, ['group_content' => $group_content], ['langcode' => $this->interfaceLanguage->getId()], $bubbleable_metadata);
      $this->assertEquals($output, $expected, new FormattableMarkup('Group content token %token replaced.', ['%token' => $token]));
      $this->assertEquals($bubbleable_metadata, $metadata_tests[$token]);
    }
  }

}
