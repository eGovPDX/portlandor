<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests for Rdf integration.
 *
 * @group ds
 */
class RdfTest extends FastTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field_ui',
    'ds',
    'rdf',
  ];

  /**
   * Test rdf integration.
   */
  public function testRdf() {
    \Drupal::service('module_installer')->install(['ds_test_rdf']);

    /* @var \Drupal\node\NodeInterface $node */
    $node = $this->entitiesTestSetup();

    // Look at node and verify the rdf tags are available.
    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->responseContains('typeof="schema:Article');
  }

}
