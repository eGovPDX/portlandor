<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Tests the behavior of group role storage handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Storage\GroupRoleStorage
 * @group group
 */
class GroupRoleStorageTest extends GroupKernelTestBase {

  /**
   * The group role storage handler.
   *
   * @var \Drupal\group\Entity\Storage\GroupRoleStorageInterface
   */
  protected $storage;

  /**
   * The group to run tests with.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The user to get added to the test group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->storage = $this->entityTypeManager->getStorage('group_role');

    $this->group = $this->createGroup();
    $this->account = $this->createUser();

    $this->storage->create([
      'id' => 'default-editor',
      'label' => 'Default editor',
      'weight' => 0,
      'group_type' => 'default',
    ])->save();
  }

  /**
   * Tests the loading of group roles by user and group.
   *
   * @covers ::loadByUserAndGroup
   */
  public function testLoadByUserAndGroup() {
    $this->compareMemberRoles([], FALSE, 'User has no explicit group roles as they are not a member.');
    $this->compareMemberRoles(['default-outsider'], TRUE, 'User initially has implicit outsider role.');

    // Grant the user a new site role and check the storage.
    $this->entityTypeManager->getStorage('user_role')->create(['id' => 'publisher'])->save();
    $this->account->addRole('publisher');
    $this->account->save();
    $group_role_id = _group_role_synchronizer()->getGroupRoleId('default', 'publisher');
    $this->compareMemberRoles([], FALSE, 'User has no explicit group roles as they are not a member.');
    $this->compareMemberRoles([$group_role_id, 'default-outsider'], TRUE, 'User has implicit and synchronized outsider roles.');

    // From this point on we test with the user as a member.
    $this->group->addMember($this->account);
    $this->compareMemberRoles([], FALSE, 'User still has no explicit group roles.');
    $this->compareMemberRoles(['default-member'], TRUE, 'User has implicit member role now that they have joined the group.');

    // Grant the member a new group role and check the storage.
    // @todo This displays a desperate need for addRole() and removeRole().
    $membership = $this->group->getMember($this->account)->getGroupContent();
    $membership->group_roles[] = 'default-editor';
    $membership->save();
    $this->compareMemberRoles(['default-editor'], FALSE, 'User has the editor group role.');
    $this->compareMemberRoles(['default-editor', 'default-member'], TRUE, 'User also has implicit member role.');
  }

  /**
   * Asserts that the test user's group roles match a provided list of IDs.
   *
   * @param string[] $expected
   *   The group role IDs we expect the user to have.
   * @param bool $include_implied
   *   Whether to include internal group roles.
   * @param string $message
   *   The message to display for the assertion.
   */
  protected function compareMemberRoles($expected, $include_implied, $message) {
    $group_roles = $this->storage->loadByUserAndGroup($this->account, $this->group, $include_implied);
    $actual = array_keys($group_roles);
    sort($actual);
    sort($expected);
    $this->assertEquals($expected, $actual, $message);
  }

}
