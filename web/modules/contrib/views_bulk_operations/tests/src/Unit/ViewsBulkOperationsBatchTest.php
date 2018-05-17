<?php

namespace Drupal\Tests\views_bulk_operations\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\views\Entity\View;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\ViewsBulkOperationsBatch
 * @group views_bulk_operations
 */
class ViewsBulkOperationsBatchTest extends UnitTestCase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);
  }

  /**
   * Returns a stub ViewsBulkOperationsActionProcessor that returns dummy data.
   *
   * @return \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor
   *   A mocked action processor.
   */
  public function getViewsBulkOperationsActionProcessorStub($entities_count) {
    $actionProcessor = $this->getMockBuilder('Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor')
      ->disableOriginalConstructor()
      ->getMock();
    $actionProcessor->expects($this->any())
      ->method('getEntity')
      ->will($this->returnValue(new \stdClass()));

    $actionProcessor->expects($this->any())
      ->method('populateQueue')
      ->will($this->returnValue($entities_count));

    $actionProcessor->expects($this->any())
      ->method('process')
      ->will($this->returnCallback(function () use ($entities_count) {
        $return = [];
        for ($i = 0; $i < $entities_count; $i++) {
          $return[] = 'Some action';
        }
        return $return;
      }));

    return $actionProcessor;
  }

  /**
   * Tests the getBatch() method.
   *
   * @covers ::getBatch
   */
  public function testGetBatch() {
    $data = [
      'list' => [[0, 'en', 'node', 1]],
      'some_data' => [],
      'action_label' => '',
    ];
    $batch = TestViewsBulkOperationsBatch::getBatch($data);
    $this->assertArrayHasKey('title', $batch);
    $this->assertArrayHasKey('operations', $batch);
    $this->assertArrayHasKey('finished', $batch);
  }

  /**
   * Tests the operation() method.
   *
   * @covers ::operation
   */
  public function testOperation() {
    $batch_size = 2;
    $entities_count = 10;

    $this->container->set('views_bulk_operations.processor', $this->getViewsBulkOperationsActionProcessorStub($batch_size));

    $view = new View(['id' => 'test_view'], 'view');
    $view_storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $view_storage->expects($this->any())
      ->method('load')
      ->with('test_view')
      ->will($this->returnValue($view));

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getStorage')
      ->with('view')
      ->will($this->returnValue($view_storage));
    $this->container->set('entity.manager', $entity_manager);

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();

    $executable->result = [];

    // We set only $batch_size entities because
    // $view->setItemsPerPage will not have effect.
    for ($i = 0; $i < $batch_size; $i++) {
      $row = new \stdClass();
      $row->_entity = new \stdClass();
      $executable->result[] = $row;
    }

    $viewExecutableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $viewExecutableFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValue($executable));
    $this->container->set('views.executable', $viewExecutableFactory);

    $data = [
      'view_id' => 'test_view',
      'display_id' => 'test_display',
      'batch_size' => $batch_size,
      'list' => [],
    ];
    $context = [
      'sandbox' => [
        'processed' => 0,
        'total' => $entities_count,
      ],
    ];

    TestViewsBulkOperationsBatch::operation($data, $context);

    $this->assertEquals(count($context['results']['operations']), $batch_size);
    $this->assertEquals($context['finished'], ($batch_size / $entities_count));
  }

  /**
   * Tests the finished() method.
   *
   * @covers ::finished
   */
  public function testFinished() {
    $results = ['operations' => ['Some operation', 'Some operation']];
    TestViewsBulkOperationsBatch::finished(TRUE, $results, []);
    $this->assertEquals(TestViewsBulkOperationsBatch::message(), 'Action processing results: Some operation (2).');

    $results = ['operations' => ['Some operation1', 'Some operation2']];
    TestViewsBulkOperationsBatch::finished(TRUE, $results, []);
    $this->assertEquals(TestViewsBulkOperationsBatch::message(), 'Action processing results: Some operation1 (1), Some operation2 (1).');
  }

}
