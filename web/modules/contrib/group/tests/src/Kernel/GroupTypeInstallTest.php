<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests the creation of group type entities during extension install.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupType
 * @group group
 */
class GroupTypeInstallTest extends GroupKernelTestBase {

  /**
   * Tests special behavior during group type creation.
   *
   * @covers ::postSave
   */
  public function testInstall() {
    // Check that the group type was installed properly.
    /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager
      ->getStorage('group_type')
      ->load('default');

    $this->assertNotNull($group_type, 'Group type was loaded successfully.');

    // Check that the special group roles give priority to the Yaml files.
    $outsider = $group_type->getOutsiderRole();
    $this->assertEquals(['join group', 'view group'], $outsider->getPermissions(), 'Outsider role was created from Yaml file.');

    // Check that special group roles are being created without Yaml files.
    /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
    $group_role = $this->entityTypeManager
      ->getStorage('group_role')
      ->load($group_type->getAnonymousRoleId());

    $this->assertNotNull($group_role, 'Anonymous role was created without a Yaml file.');

    // Check that the enforced plugins give priority to the Yaml files.
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    $plugin = $group_type->getContentPlugin('group_membership');
    $config = $plugin->getConfiguration();

    $this->assertEquals('99', $config['group_cardinality'], 'Enforced group_membership plugin was created from Yaml file.');
  }

}
