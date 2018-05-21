<?php

namespace Drupal\Tests\lightning_core\Kernel;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\lightning_core\UpdateManager;

/**
 * @coversDefaultClass \Drupal\lightning_core\UpdateManager
 *
 * @group lightning
 * @group lightning_core
 */
class UpdateManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_core', 'system', 'user'];

  /**
   * @covers ::getAvailable
   */
  public function testGetAvailable() {
    $discovery = $this->prophesize(DiscoveryInterface::class);
    $discovery->getDefinitions()->willReturn([
      'fubar:1.2.1' => [
        'id' => '1.2.1',
        'provider' => 'fubar',
      ],
      'fubar:1.2.2' => [
        'id' => '1.2.2',
        'provider' => 'fubar',
      ],
      'fubar:1.2.3' => [
        'id' => '1.2.3',
        'provider' => 'fubar',
      ],
    ]);

    $this->container->get('config.factory')
      ->getEditable(UpdateManager::CONFIG_NAME)
      ->set('fubar', '1.2.2')
      ->save();

    $update_manager = new UpdateManager(
      $this->container->get('container.namespaces'),
      $this->container->get('class_resolver'),
      $this->container->get('config.factory'),
      $discovery->reveal()
    );

    $definitions = $update_manager->getAvailable();
    $this->assertCount(1, $definitions);
    $this->assertArrayHasKey('fubar:1.2.3', $definitions);
  }

}
