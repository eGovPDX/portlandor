<?php

namespace Drupal\system\Tests\Installer;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\simpletest\InstallerTestBase;

/**
 * Verifies that installing from existing configuration works.
 *
 * @group Installer
 */
class InstallerExistingConfigMultilingualConfigDirTest extends InstallerExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_config_install_multilingual';

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
        $archiver = new ArchiveTar($this->getConfigTarball(), 'gz');

        // Create a profile for testing.
        $info = [
          'type' => 'profile',
          'core' => \Drupal::CORE_COMPATIBILITY,
          'name' => 'Configuration installation test profile (' . $this->profile . ')',
          'config_install' => TRUE,
        ];
        // File API functions are not available yet.
        $path = $this->siteDirectory . '/profiles/' . $this->profile;
        mkdir($path, 0777, TRUE);
        file_put_contents("$path/{$this->profile}.info.yml", Yaml::encode($info));

        // Create config/sync directory and extract tarball contents to it.
        $config_sync_directory = $this->siteDirectory . '/config/sync';
        mkdir($config_sync_directory, 0777, TRUE);
        $files = [];
        $list = $archiver->listContent();
        if (is_array($list)) {
            /** @var array $list */
            foreach ($list as $file) {
                $files[] = $file['filename'];
            }
            $archiver->extractList($files, $config_sync_directory);
        }

        $this->settings['config_directories'] = [
          CONFIG_SYNC_DIRECTORY => (object) [
            'value' => $config_sync_directory,
            'required' => TRUE,
          ],
        ];

        InstallerTestBase::setUp();
    }

  /**
   * @inheritDoc
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../tests/fixtures/config_install/multilingual.tar.gz';
  }

}
