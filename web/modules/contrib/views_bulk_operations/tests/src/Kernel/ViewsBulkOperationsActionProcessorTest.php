<?php

namespace Drupal\Tests\views_bulk_operations\Kernel;

use Drupal\node\NodeInterface;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor
 * @group views_bulk_operations
 */
class ViewsBulkOperationsActionProcessorTest extends ViewsBulkOperationsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->createTestNodes([
      'page' => [
        'count' => 20,
      ],
    ]);
  }

  /**
   * Tests general functionality of ViewsBulkOperationsActionProcessor.
   *
   * @covers ::getPageList
   * @covers ::populateQueue
   * @covers ::process
   */
  public function testViewsbulkOperationsActionProcessor() {
    $vbo_data = [
      'view_id' => 'views_bulk_operations_test',
      'action_id' => 'views_bulk_operations_simple_test_action',
      'configuration' => [
        'preconfig' => 'test',
      ],
    ];

    // Test executing all view results first.
    $results = $this->executeAction($vbo_data);

    // The default batch size is 10 and there are 20 result rows total
    // (10 nodes, each having a translation), check messages:
    $this->assertEquals('Processed 10 of 20 entities.', $results['messages'][0]);
    $this->assertEquals('Processed 20 of 20 entities.', $results['messages'][1]);
    $this->assertEquals(20, $results['operations']['Test']);

    // For a more advanced test, check if randomly selected entities
    // have been unpublished.
    $vbo_data = [
      'view_id' => 'views_bulk_operations_test',
      'action_id' => 'views_bulk_operations_advanced_test_action',
      'preconfiguration' => [
        'test_preconfig' => 'test',
        'test_config' => 'unpublish',
      ],
    ];

    // Get list of rows to process from different view pages.
    $selection = [0, 3, 6, 8, 15, 16, 18];
    $vbo_data['list'] = $this->getResultsList($vbo_data, $selection);

    // Execute the action.
    $results = $this->executeAction($vbo_data);

    $nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');

    $statuses = [];

    foreach ($this->testNodesData as $id => $lang_data) {
      $node = $nodeStorage->load($id);
      $statuses[$id] = intval($node->status->value);
    }

    foreach ($statuses as $id => $status) {
      foreach ($vbo_data['list'] as $item) {
        if ($item[3] == $id) {
          $this->assertEquals(NodeInterface::NOT_PUBLISHED, $status);
          break 2;
        }
      }
      $this->assertEquals(NodeInterface::PUBLISHED, $status);
    }
  }

}
