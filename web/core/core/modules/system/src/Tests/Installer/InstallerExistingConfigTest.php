<?php

namespace Drupal\system\Tests\Installer;

/**
 * Verifies that installing from existing configuration works.
 *
 * @group Installer
 */
class InstallerExistingConfigTest extends InstallerExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUpSite() {
    // The configuration is from a site installed in French.
    // So after selecting the profile the installer detects that the site must
    // be installed in French, thus we change the button translation.
    $this->translations['Save and continue'] = 'Enregistrer et continuer';
    parent::setUpSite();
  }

  /**
   * @inheritDoc
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../tests/fixtures/config_install/testing_config_install.tar.gz';
  }

}
