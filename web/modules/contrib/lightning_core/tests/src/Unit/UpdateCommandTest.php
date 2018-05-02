<?php

namespace Drupal\Tests\lightning_core\Unit;

use Drupal\lightning_core\Command\UpdateCommand;
use Drupal\lightning_core\UpdateManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\lightning_core\Command\UpdateCommand
 *
 * @group lightning
 * @group lightning_core
 */
class UpdateCommandTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testClass() {
    new UpdateCommand(
      $this->createMock(UpdateManager::class)
    );
  }

}
