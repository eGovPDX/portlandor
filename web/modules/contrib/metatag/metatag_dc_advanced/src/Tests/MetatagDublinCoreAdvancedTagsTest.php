<?php

namespace Drupal\metatag_dc_advanced\Tests;

use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Dublin Core Advanced tags work correctly.
 *
 * @group metatag
 */
class MetatagDublinCoreAdvancedTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  private $tags = [];

  /**
   * {@inheritdoc}
   */
  private $testTag = 'meta';

  /**
   * {@inheritdoc}
   */
  private $testNameAttribute = 'property';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_dc_advanced';
    parent::setUp();
  }

}
