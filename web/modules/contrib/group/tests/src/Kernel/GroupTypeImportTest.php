<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests the import or synchronization of group type entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupType
 * @group group
 */
class GroupTypeImportTest extends GroupKernelTestBase {

  /**
   * Tests special behavior during group type import.
   *
   * @covers ::postSave
   */
  public function testImport() {
    // Simulate config data to import.
    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($active, $sync);

    // Manually add the 'import' group type to the synchronization directory.
    $test_dir = __DIR__ . '/../../modules/group_test_config/sync';
    $sync_dir = config_get_config_directory(CONFIG_SYNC_DIRECTORY);
    $this->assertNotFalse(file_unmanaged_copy("$test_dir/group.type.import.yml", "$sync_dir/group.type.import.yml"), 'Copied the group type Yaml file to the sync dir.');

    // Import the content of the sync directory.
    $this->configImporter()->import();

    // Check that the group type was created.
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager
      ->getStorage('group_type')
      ->load('import');

    $this->assertNotNull($group_type, 'Import group type from sync was created.');

    // Check that no special group roles were created.
    $group_role_ids = [
      $group_type->getAnonymousRoleId(),
      $group_type->getOutsiderRoleId(),
      $group_type->getMemberRoleId(),
    ];

    $group_roles = $this->entityTypeManager
      ->getStorage('group_role')
      ->loadMultiple($group_role_ids);

    $this->assertEquals(0, count($group_roles), 'No special group roles were created.');

    // Check that no enforced plugins were installed.
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin_config = ['group_type_id' => 'import', 'id' => 'group_membership'];
    $plugin = $this->pluginManager->createInstance('group_membership', $plugin_config);

    $group_content_type = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->load($plugin->getContentTypeConfigId());

    $this->assertNull($group_content_type, 'No enforced plugins were installed.');
  }

}
