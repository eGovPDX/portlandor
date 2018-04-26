<?php

namespace Drupal\Tests\group\Kernel\Views;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the group_content_to_entity_reverse relationship handler.
 *
 * @see \Drupal\group\Plugin\views\relationship\GroupContentToEntityReverse
 *
 * @group group
 */
class GroupContentToEntityReverseRelationshipTest extends GroupContentToEntityRelationshipTest {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_content_to_entity_reverse_relationship'];

}
