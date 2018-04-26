<?php

namespace Drupal\Tests\lightning_core\Kernel\Update;

use Drupal\lightning_core\UpdateManager;

/**
 * @group lightning_core
 * @group lightning
 */
class Update8007Test extends Update8006Test {

  public function testUpdate() {
    parent::testUpdate();
    lightning_core_update_8007();

    $factory = $this->container->get('config.factory');

    $this->assertFalse(
      $factory->get(UpdateManager::CONFIG_NAME)->isNew()
    );
    $this->assertTrue(
      $factory->get('lightning.versions')->isNew()
    );
  }

}
