<?php

namespace Drupal\Tests\jsonapi\Unit\Normalizer\Value;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\jsonapi\Normalizer\Value\RelationshipItemNormalizerValue;
use Drupal\jsonapi\Normalizer\Value\RelationshipNormalizerValue;
use Drupal\jsonapi\Normalizer\Value\FieldItemNormalizerValue;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\Value\RelationshipNormalizerValue
 * @group jsonapi
 *
 * @internal
 */
class RelationshipNormalizerValueTest extends UnitTestCase {

  /**
   * The cache contexts manager.
   *
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cacheContextsManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cacheContextsManager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->cacheContextsManager->method('assertValidTokens')->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $this->cacheContextsManager);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::rasterizeValue
   * @dataProvider rasterizeValueProvider
   */
  public function testRasterizeValue($values, $cardinality, $expected, CacheableMetadata $expected_cacheability) {
    $link_manager = $this->prophesize(LinkManager::class);
    $link_manager
      ->getEntityLink(Argument::any(), Argument::any(), Argument::type('array'), Argument::type('string'))
      ->willReturn('dummy_entity_link');
    $resource_type = new ResourceType($this->randomMachineName(), $this->randomMachineName(), NULL);
    $resource_type->setRelatableResourceTypes([
      'ipsum' => [$resource_type],
    ]);
    $object = new RelationshipNormalizerValue(AccessResult::allowed()->cachePerUser()->addCacheTags(['relationship:foo']), $values, $cardinality, [
      'link_manager' => $link_manager->reveal(),
      'host_entity_id' => 'lorem',
      'resource_type' => $resource_type,
      'field_name' => 'ipsum',
    ]);
    $this->assertEquals($expected, $object->rasterizeValue());
    $this->assertSame($expected_cacheability->getCacheContexts(), $object->getCacheContexts());
    $this->assertSame($expected_cacheability->getCacheTags(), $object->getCacheTags());
    $this->assertSame($expected_cacheability->getCacheMaxAge(), $object->getCacheMaxAge());
  }

  /**
   * Data provider fortestRasterizeValue.
   */
  public function rasterizeValueProvider() {
    $uid_raw = 1;
    $uid1 = $this->prophesize(RelationshipItemNormalizerValue::class);
    $uid1->rasterizeValue()->willReturn(['type' => 'user', 'id' => $uid_raw++]);
    $uid1->getInclude()->willReturn(NULL);
    $uid1->getCacheContexts()->willReturn(['ccfoo']);
    $uid1->getCacheTags()->willReturn(['ctfoo']);
    $uid1->getCacheMaxAge()->willReturn(15);
    $uid2 = $this->prophesize(RelationshipItemNormalizerValue::class);
    $uid2->rasterizeValue()->willReturn(['type' => 'user', 'id' => $uid_raw]);
    $uid2->getInclude()->willReturn(NULL);
    $uid2->getCacheContexts()->willReturn(['ccbar']);
    $uid2->getCacheTags()->willReturn(['ctbar']);
    $uid2->getCacheMaxAge()->willReturn(10);
    $links = [
      'self' => 'dummy_entity_link',
      'related' => 'dummy_entity_link',
    ];
    return [
      [[$uid1->reveal()], 1, [
        'data' => ['type' => 'user', 'id' => 1],
        'links' => $links,
      ],
        (new CacheableMetadata())
          ->setCacheContexts(['ccfoo', 'user'])
          ->setCacheTags(['ctfoo', 'relationship:foo'])
          ->setCacheMaxAge(15),
      ],
      [
        [$uid1->reveal(), $uid2->reveal()], 2, [
          'data' => [
            ['type' => 'user', 'id' => 1],
            ['type' => 'user', 'id' => 2],
          ],
          'links' => $links,
        ],
        (new CacheableMetadata())
          ->setCacheContexts(['ccbar', 'ccfoo', 'user'])
          ->setCacheTags(['ctbar', 'ctfoo', 'relationship:foo'])
          ->setCacheMaxAge(10),
      ],
    ];
  }

  /**
   * @covers ::rasterizeValue
   *
   * @expectedException \RuntimeException
   */
  public function testRasterizeValueFails() {
    $uid1 = $this->prophesize(FieldItemNormalizerValue::class);
    $uid1->rasterizeValue()->willReturn(1);
    $uid1->getInclude()->willReturn(NULL);
    $link_manager = $this->prophesize(LinkManager::class);
    $link_manager
      ->getEntityLink(Argument::any(), Argument::any(), Argument::type('array'), Argument::type('string'))
      ->willReturn('dummy_entity_link');
    $object = new RelationshipNormalizerValue([$uid1->reveal()], 1, [
      'link_manager' => $link_manager->reveal(),
      'host_entity_id' => 'lorem',
      'resource_type' => new ResourceType($this->randomMachineName(), $this->randomMachineName(), NULL),
      'field_name' => 'ipsum',
    ]);
    $object->rasterizeValue();
    // If the exception was not thrown, then the following fails.
    $this->assertTrue(FALSE);
  }

}
