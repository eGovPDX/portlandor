<?php

namespace Drupal\Tests\lightning_core\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\lightning_core\UpdateManager;

/**
 * @group lightning
 * @group lightning_core
 */
class Update8006Test extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/Update8006Test.php.gz',
    ];
  }

  public function testUpdate() {
    $old_name = 'lightning.versions';
    $new_name = UpdateManager::CONFIG_NAME;

    // When doing a lot of updates, it's possible that lightning_core.versions
    // will exist before update 8007 runs, so we need to simulate that case.
    $this->config($new_name)->set('foo', '5.1.0')->save();

    // Ensure that update 8006 works if lightning.versions exists.
    $this->config($old_name)
      ->set('foo', '5.0.0')
      ->set('bar', '1.0.0')
      ->save();

    $this->runUpdates();

    // Assert that lightning.versions is gone, but its data is preserved.
    $this->assertTrue($this->config($old_name)->isNew());
    $new = $this->config($new_name);
    $this->assertSame('5.1.0', $new->get('foo'));
    $this->assertSame('1.0.0', $new->get('bar'));
  }

}
