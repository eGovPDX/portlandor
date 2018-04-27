<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests whether group roles are actually synchronized.
 *
 * @group group
 */
class GroupRoleSynchronizationTest extends GroupKernelTestBase {

  /**
   * The group role synchronizer service.
   *
   * @var \Drupal\group\GroupRoleSynchronizer
   */
  protected $groupRoleSynchronizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // The sheer functionality of the synchronizer service is tested elsewhere
    // in \Drupal\Tests\group\Unit\GroupRoleSynchronizerTest, so we can rely on
    // it for the sake of this test.
    $this->groupRoleSynchronizer = $this->container->get('group_role.synchronizer');
  }

  /**
   * Creates a user role.
   *
   * @param string $id
   *   The ID of the user role to create. Label will be set to the ID with the
   *   first letter converted to upper case.
   *
   * @return \Drupal\user\Entity\Role
   *   The created user role.
   */
  protected function createUserRole($id) {
    $role = $this->entityTypeManager->getStorage('user_role')
      ->create([
        'id' => $id,
        'label' => ucfirst($id),
      ]);
    $role->save();
    return $role;
  }

  /**
   * Tests whether a new global role syncs to group roles.
   */
  public function testGlobalRoleCreation() {
    $role = $this->createUserRole('editor');
    $defaultGroupRoleId = $this->groupRoleSynchronizer->getGroupRoleId('default', $role->id());
    $otherGroupRoleId = $this->groupRoleSynchronizer->getGroupRoleId('other', $role->id());
    $group_roles = $this->entityTypeManager->getStorage('group_role')->loadMultiple();

    $this->assertArrayHasKey($defaultGroupRoleId, $group_roles, 'Synchronized role found for default group type');
    $this->assertArrayHasKey($otherGroupRoleId, $group_roles, 'Synchronized role found for other group type');
    $this->assertEquals($role->label(), $group_roles[$otherGroupRoleId]->label(), 'Synchronized roles share the same label');
  }

  /**
   * Tests whether an updated global role syncs to group roles.
   */
  public function testGlobalRoleUpdate() {
    $role = $this->createUserRole('editor');

    $role->set('label', 'Super-editor');
    $role->save();
    $defaultGroupRoleId = $this->groupRoleSynchronizer->getGroupRoleId('default', $role->id());
    $group_role = $this->entityTypeManager->getStorage('group_role')->load($defaultGroupRoleId);

    $this->assertEquals('Super-editor', $group_role->label(), 'Updated synchronized roles share the same label');
  }

  /**
   * Tests whether a deleted global role syncs to group roles.
   */
  public function testGlobalRoleDelete() {
    $role = $this->createUserRole('editor');

    $role->delete();
    $defaultGroupRoleId = $this->groupRoleSynchronizer->getGroupRoleId('default', 'editor');
    $group_roles = $this->entityTypeManager->getStorage('group_role')->loadMultiple();

    $this->assertArrayNotHasKey($defaultGroupRoleId, $group_roles, 'Synchronized role not found for deleted global role');
  }

  /**
   * Tests whether a new group type receives synchronized group roles.
   */
  public function testGroupTypeCreation() {
    $role = $this->createUserRole('editor');

    $group_type = $this->entityTypeManager->getStorage('group_type')->create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $group_type->save();
    $group_roles = $this->entityTypeManager->getStorage('group_role')->loadMultiple();
    $group_role_id = $this->groupRoleSynchronizer->getGroupRoleId($group_type->id(), $role->id());

    $this->assertArrayHasKey($group_role_id, $group_roles, 'Synchronized role found for new group type');
  }

}
