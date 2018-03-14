<?php

namespace Drupal\Tests\lightning_core\Functional\Update;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

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
    // The regression that this test guards against is caused if there is a
    // lightning.versions config object, so ensure it exists before we run
    // updates.
    $config = $this->container->get('config.factory')
      ->get('lightning.versions');

    $this->assertFalse($config->isNew());

    $this->runUpdates();
  }

}
