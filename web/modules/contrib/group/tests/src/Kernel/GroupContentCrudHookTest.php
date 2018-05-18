<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests the way group content entities react to entity CRUD events.
 *
 * @group group
 */
class GroupContentCrudHookTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Required to be able to delete accounts. See User::postDelete().
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests that a grouped entity deletion triggers group content deletion.
   */
  public function testGroupedEntityDeletion() {
    $account = $this->createUser();
    $group = $this->createGroup(['uid' => $account->id()]);

    $count = count($group->getContent());
    $account->delete();
    $this->assertCount($count - 1, $group->getContent(), "Deleting the group owner's account reduces the group content count by one.");
  }

  /**
   * Tests that an ungrouped entity deletion triggers no group content deletion.
   */
  public function testUngroupedEntityDeletion() {
    $group = $this->createGroup();

    $count = count($group->getContent());
    $this->createUser()->delete();
    $this->assertCount($count, $group->getContent(), "Deleting an ungrouped user account does not remove any group content.");
  }

}
