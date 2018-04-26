<?php

namespace Drupal\Tests\facets\Unit\Processor;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\facets\Processor\ProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginManager;
use Drupal\Tests\UnitTestCase;
use Zend\Stdlib\ArrayObject;

/**
 * Unit test for Processor plugin manager.
 *
 * @group facets
 */
class ProcessorPluginManagerTest extends UnitTestCase {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The plugin discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $discovery;

  /**
   * The plugin factory.
   *
   * @var \Drupal\Component\Plugin\Factory\DefaultFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $factory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The translator interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $translator;

  /**
   * The plugin manager under test.
   *
   * @var \Drupal\facets\Processor\ProcessorPluginManager
   */
  public $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->discovery = $this->getMock(DiscoveryInterface::class);

    $this->factory = $this->getMockBuilder(DefaultFactory::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->moduleHandler = $this->getMock(ModuleHandlerInterface::class);

    $this->cache = $this->getMock(CacheBackendInterface::class);

    $this->translator = $this->getMock(TranslationInterface::class);

    $namespaces = new ArrayObject();

    $this->sut = new ProcessorPluginManager($namespaces, $this->cache, $this->moduleHandler, $this->translator);
    $discovery_property = new \ReflectionProperty($this->sut, 'discovery');
    $discovery_property->setAccessible(TRUE);
    $discovery_property->setValue($this->sut, $this->discovery);
    $factory_property = new \ReflectionProperty($this->sut, 'factory');
    $factory_property->setAccessible(TRUE);
    $factory_property->setValue($this->sut, $this->factory);
  }

  /**
   * Tests plugin manager constructor.
   */
  public function testConstruct() {
    $namespaces = new ArrayObject();
    $sut = new ProcessorPluginManager($namespaces, $this->cache, $this->moduleHandler, $this->translator);
    $this->assertInstanceOf(ProcessorPluginManager::class, $sut);
  }

  /**
   * Tests plugin manager's getDefinitions method.
   */
  public function testGetDefinitions() {
    $definitions = [
      'foo' => [
        'label' => $this->randomMachineName(),
      ],
    ];
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->assertSame($definitions, $this->sut->getDefinitions());
  }

  /**
   * Tests processing stages.
   */
  public function testGetProcessingStages() {
    $namespaces = new ArrayObject();
    $sut = new ProcessorPluginManager($namespaces, $this->cache, $this->moduleHandler, $this->translator);

    $stages = [
      ProcessorInterface::STAGE_PRE_QUERY,
      ProcessorInterface::STAGE_POST_QUERY,
      ProcessorInterface::STAGE_BUILD,
      ProcessorInterface::STAGE_SORT,
    ];

    $this->assertEquals($stages, array_keys($sut->getProcessingStages()));
  }

}
