<?php

namespace Drupal\system\Tests\Installer;

/**
 * Verifies that installing from existing configuration works.
 *
 * @group Installer
 */
class InstallerExistingConfigMultilingualTest extends InstallerExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_config_install_multilingual';

  /**
   * @inheritDoc
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../tests/fixtures/config_install/multilingual.tar.gz';
  }

}
