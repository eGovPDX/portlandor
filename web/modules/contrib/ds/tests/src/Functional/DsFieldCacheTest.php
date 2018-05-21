<?php

namespace Drupal\Tests\ds\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Cache field test.
 *
 * @group ds
 */
class DsFieldCacheTest extends FastTestBase {

  use NodeCreationTrait;
  use DsTestTrait;

  public static $modules = ['page_cache', 'dynamic_page_cache'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test a DS field that returns cache data.
   */
  public function testCachedDsField() {
    $fields = [
      'fields[test_caching_field][region]' => 'left',
      'fields[test_caching_field][label]' => 'above',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/display', ['layout' => 'ds_2col'], t('Save'));
    $this->dsConfigureUi($fields);

    // Create and visit the node so that it is cached as empty, ensure the title
    // doesn't appear.
    $node = $this->createNode(['type' => 'article']);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextNotContains('DsField Shown');

    // Enable our toggle flag and invalidate the cache so that our field should
    // appear.
    \Drupal::state()->set('ds_test_show_field', TRUE);
    Cache::invalidateTags(['ds_my_custom_tags']);

    // Visit the node and assert that it now appears.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextContains('DsField Shown');
  }

}
