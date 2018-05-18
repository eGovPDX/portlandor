<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Tests the behavior of group creators.
 *
 * @todo Move to Drupal\Tests\group\Functional\GroupCreatorWizardTest when we
 *   remove the functionality that auto-creates creator memberships while saving
 *   a new group programmatically.
 *
 * @group group
 */
class GroupCreatorTest extends GroupKernelTestBase {

  /**
   * Gets the roles for the group creator account's membership.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to load the group role entities for.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to find the user's role entities in.
   *
   * @return \Drupal\group\Entity\GroupRoleInterface[]
   *   The group roles matching the criteria.
   */
  protected function getCreatorRoles(AccountInterface $account, GroupInterface $group) {
    /** @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface $group_role_storage */
    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    return $group_role_storage->loadByUserAndGroup($account, $group, FALSE);
  }

  /**
   * Tests that a group creator is automatically a member.
   */
  public function testCreatorGetsMembership() {
    $group = $this->createGroup();
    $account = $this->getCurrentUser();
    $this->assertNotFalse($group->getMember($account), 'Membership could be loaded for the group creator.');
    $this->assertCount(0, $this->getCreatorRoles($account, $group), 'Membership has zero roles.');
  }

  /**
   * Tests that a group creator gets the configured roles.
   *
   * @depends testCreatorGetsMembership
   */
  public function testCreatorRoles() {
    /* @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager->getStorage('group_type')->load('default');
    $group_type->set('creator_roles', ['default-custom']);
    $group_type->save();

    $group_roles = $this->getCreatorRoles($this->getCurrentUser(), $this->createGroup());
    $this->assertCount(1, $group_roles, 'Membership has one role.');
    $this->assertEquals('default-custom', reset($group_roles)->id(), 'Membership has the custom role.');
  }

  /**
   * Tests that a group creator is not automatically made a member.
   */
  public function testCreatorDoesNotGetMembership() {
    /* @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager->getStorage('group_type')->load('default');
    $group_type->set('creator_membership', FALSE);
    $group_type->save();

    $group_membership = $this->createGroup()->getMember($this->getCurrentUser());
    $this->assertFalse($group_membership, 'Membership could not be loaded for the group creator.');
  }

}
