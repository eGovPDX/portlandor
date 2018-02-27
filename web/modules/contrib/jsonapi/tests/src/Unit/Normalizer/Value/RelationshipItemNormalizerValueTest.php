<?php

namespace Drupal\Tests\jsonapi\Unit\Normalizer\Value;

use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Normalizer\Value\RelationshipItemNormalizerValue;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\Value\RelationshipItemNormalizerValue
 * @group jsonapi
 */
class RelationshipItemNormalizerValueTest extends UnitTestCase {

  /**
   * @covers ::rasterizeValue
   * @dataProvider rasterizeValueProvider
   */
  public function testRasterizeValue($values, $entity_type_id, $bundle, $expected) {
    $object = new RelationshipItemNormalizerValue($values, new ResourceType($entity_type_id, $bundle, NULL));
    $this->assertEquals($expected, $object->rasterizeValue());
  }

  /**
   * Data provider for testRasterizeValue.
   */
  public function rasterizeValueProvider() {
    return [
      [['target_id' => 1], 'node', 'article', ['type' => 'node--article', 'id' => 1]],
      [['value' => 1], 'node', 'page', ['type' => 'node--page', 'id' => 1]],
      [[1], 'node', 'foo', ['type' => 'node--foo', 'id' => 1]],
      [[], 'node', 'bar', []],
      [[NULL], 'node', 'baz', NULL],
    ];
  }

}
