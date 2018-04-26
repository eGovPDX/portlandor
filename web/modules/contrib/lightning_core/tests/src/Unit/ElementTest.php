<?php

namespace Drupal\Tests\lightning_core\Unit;

use Drupal\lightning_core\Element;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\lightning_core\Element
 *
 * @group lightning_core
 * @group lightning
 */
class ElementTest extends UnitTestCase {

  /**
   * @covers ::oxford
   */
  public function testOxford() {
    $this->assertSame('foo, bar, and baz', Element::oxford(['foo', 'bar', 'baz']));
    $this->assertSame('foo and bar', Element::oxford(['foo', 'bar']));
    $this->assertSame('foo', Element::oxford(['foo']));
    $this->assertEmpty(Element::oxford([]));

    $this->assertSame('Larry, Moe, or Curly', Element::oxford(['Larry', 'Moe', 'Curly'], 'or'));
  }

  /**
   * @covers ::toTail
   */
  public function testToTail() {
    $items = ['here', 'everywhere', 'there'];
    $items = array_combine($items, $items);
    Element::toTail($items, 'everywhere');
    $this->assertSame(['here', 'there', 'everywhere'], array_keys($items));
  }

  /**
   * @covers ::order
   */
  public function testOrder() {
    $items = [
      'apple' => 1,
      'orange' => 2,
      'banana' => 3,
    ];
    Element::order($items, ['banana', 'apple', 'orange']);
    $this->assertSame(['banana', 'apple', 'orange'], array_keys($items));
  }

}
