<?php

namespace Drupal\system\Tests\Installer;

/**
 * Verifies that installing from existing configuration works.
 *
 * @group Installer
 */
class InstallerExistingConfigNoConfigTest extends InstallerExistingConfigTestBase {

  protected $profile = 'no_config_profile';

  /**
   * Final installer step: Configure site.
   */
  protected function setUpSite() {
    // There are errors therefore there is nothing to do here.
    return;
  }

  /**
   * @inheritDoc
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../tests/fixtures/config_install/testing_config_install_no_config.tar.gz';
  }

  public function testConfigSync() {
    $this->assertTitle('Configuration validation | Drupal');
    $this->assertText('The configuration synchronization failed validation.');
    $this->assertText('This import is empty and if applied would delete all of your configuration, so has been rejected.');

    // Ensure there is no continuation button.
    $this->assertNoText('Save and continue');
    $this->assertNoFieldById('edit-submit');
  }

}
