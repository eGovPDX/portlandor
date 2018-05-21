<?php

namespace Drupal\Tests\facets\Unit\FacetSource;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\Tests\UnitTestCase;
use Zend\Stdlib\ArrayObject;

/**
 * Unit test for plugin manager.
 *
 * @group facets
 */
class FacetSourcePluginManagerTest extends UnitTestCase {

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
   * The plugin manager under test.
   *
   * @var \Drupal\facets\FacetSource\FacetSourcePluginManager
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

    $namespaces = new ArrayObject();

    $this->sut = new FacetSourcePluginManager($namespaces, $this->cache, $this->moduleHandler);
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
    $sut = new FacetSourcePluginManager($namespaces, $this->cache, $this->moduleHandler);
    $this->assertInstanceOf(FacetSourcePluginManager::class, $sut);
  }

  /**
   * Tests plugin manager's getDefinitions method.
   */
  public function testGetDefinitions() {
    $definitions = [
      'foo' => [
        'id' => 'foo_bar',
        'label' => 'Foo bar',
        'description' => 'test',
        'display_id' => 'foo',
      ],
    ];
    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->assertSame($definitions, $this->sut->getDefinitions());
  }

  /**
   * Tests plugin manager definitions.
   *
   * @dataProvider invalidDefinitions
   */
  public function testInvalidDefinitions($invalid_definition) {
    $definitions = ['foo' => [$invalid_definition]];

    $this->discovery->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->setExpectedException(PluginException::class);
    $this->sut->getDefinitions();
  }

  /**
   * Provides invalid definitions.
   *
   * @return array
   *   An invalid data provider.
   */
  public function invalidDefinitions() {
    return [
      'only id' => ['id' => 'owl'],
      'only display_id' => ['display_id' => 'search_api:owl'],
      'only label' => ['label' => 'Owl'],
      'no label' => ['id' => 'owl', 'display_id' => 'Owl'],
    ];
  }

}
