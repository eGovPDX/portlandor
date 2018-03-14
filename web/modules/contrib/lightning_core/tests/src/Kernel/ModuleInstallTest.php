<?php

namespace Drupal\Tests\lightning_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\lightning_core\UpdateManager;

/**
 * @group lightning
 * @group lightning_core
 */
class ModuleInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_core', 'system', 'user'];

  public function testKnownVersion() {
    drupal_static_reset('system_get_info');

    $this->container->get('cache.default')
      ->set('system.module.info', [
        'fubar' => [
          'version' => '8.x-2.2',
        ],
      ]);

    lightning_core_modules_installed(['fubar']);

    $version = $this->container->get('config.factory')
      ->get(UpdateManager::CONFIG_NAME)
      ->get('fubar');

    $this->assertSame('2.2.0', $version);
  }

  /**
   * @depends testKnownVersion
   */
  public function testUnknownDiscoverableVersion() {
    $discovery = $this->prophesize('\Drupal\Component\Plugin\Discovery\DiscoveryInterface');

    $discovery->getDefinitions()->willReturn([
      'fubar:2.2.0' => [
        'id' => '2.2.0',
        'provider' => 'fubar',
      ],
      'fubar:2.3.0' => [
        'id' => '2.3.0',
        'provider' => 'fubar',
      ],
    ]);

    $this->container->set('lightning.update_manager', new UpdateManager(
      $this->container->get('container.namespaces'),
      $this->container->get('class_resolver'),
      $this->container->get('config.factory'),
      $discovery->reveal()
    ));

    lightning_core_modules_installed(['fubar']);

    $version = $this->container->get('config.factory')
      ->get(UpdateManager::CONFIG_NAME)
      ->get('fubar');

    $this->assertSame('2.3.0', $version);
  }

  /**
   * @depends testUnknownDiscoverableVersion
   */
  public function testUnknownUndiscoverableVersion() {
    lightning_core_modules_installed(['fubar']);

    $version = $this->container->get('config.factory')
      ->get(UpdateManager::CONFIG_NAME)
      ->get('fubar');

    $this->assertSame(UpdateManager::VERSION_UNKNOWN, $version);
  }

}
