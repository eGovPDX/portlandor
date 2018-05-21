<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests the general behavior of group entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\Group
 * @group group
 */
class GroupTest extends GroupKernelTestBase {

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->group = $this->createGroup();
  }

  /**
   * Tests the addition of a member to a group.
   *
   * @covers ::addMember
   */
  public function testAddMember() {
    $account = $this->createUser();
    $this->assertFalse($this->group->getMember($account), 'The user is not automatically member of the group.');
    $this->group->addMember($account);
    $this->assertNotFalse($this->group->getMember($account), 'Successfully added a member.');
  }

  /**
   * Tests the removal of a member from a group.
   *
   * @covers ::removeMember
   * @depends testAddMember
   */
  public function testRemoveMember() {
    $account = $this->createUser();
    $this->group->addMember($account);
    $this->group->removeMember($account);
    $this->assertFalse($this->group->getMember($account), 'Successfully removed a member.');
  }

}
