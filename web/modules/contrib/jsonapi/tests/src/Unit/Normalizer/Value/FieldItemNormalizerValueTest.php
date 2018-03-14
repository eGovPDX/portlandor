<?php

namespace Drupal\Tests\jsonapi\Unit\Normalizer\Value;

use Drupal\jsonapi\Normalizer\Value\FieldItemNormalizerValue;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\Value\FieldItemNormalizerValue
 * @group jsonapi
 *
 * @internal
 */
class FieldItemNormalizerValueTest extends UnitTestCase {

  /**
   * @covers ::rasterizeValue
   * @dataProvider rasterizeValueProvider
   */
  public function testRasterizeValue($values, $expected) {
    $object = new FieldItemNormalizerValue($values);
    $this->assertEquals($expected, $object->rasterizeValue());
  }

  /**
   * Provider for testRasterizeValue.
   */
  public function rasterizeValueProvider() {
    return [
      [['value' => 1], 1],
      [['value' => 1, 'safe_value' => 1], ['value' => 1, 'safe_value' => 1]],
      [[], []],
      [[NULL], NULL],
      [
        [
          'lorem' => [
            'ipsum' => new FieldItemNormalizerValue([
              'dolor' => 'sid',
              'amet' => new FieldItemNormalizerValue(['value' => 'ra']),
            ]),
          ],
        ],
        ['ipsum' => ['dolor' => 'sid', 'amet' => 'ra']],
      ],
    ];
  }

}
