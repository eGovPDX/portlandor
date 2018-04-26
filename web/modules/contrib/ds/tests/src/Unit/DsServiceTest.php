<?php

namespace Drupal\Tests\ds\Unit;

use Drupal\ds\Ds;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ds\Ds
 * @group ds
 */
class DsServiceTest extends UnitTestCase {

  /**
   * @covers ::getLayouts
   */
  public function testMissingLayoutService() {
    $this->assertFalse(\Drupal::hasService('plugin.manager.core.layout'));
    $this->assertSame([], Ds::getLayouts());
  }

}
