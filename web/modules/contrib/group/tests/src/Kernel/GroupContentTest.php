<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests for the GroupContent entity.
 *
 * @group group
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupContent
 */
class GroupContentTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test'];

  /**
   * Ensure entity url templates are functional.
   *
   * @covers ::urlRouteParameters
   */
  public function testUrlRouteParameters() {
    $group = $this->createGroup();
    $account = $this->createUser();
    $group->addContent($account, 'group_membership');
    $group_content = $group->getContent('group_membership');
    foreach ($group_content as $item) {
      // Canonical.
      $expected = "/group/{$group->id()}/content/{$item->id()}";
      $this->assertEquals($expected, $item->toUrl()->toString());

      // Add form.
      $expected = "/group/{$group->id()}/content/add/group_membership?group_content_type=default-group_membership";
      $this->assertEquals($expected, $item->toUrl('add-form')->toString());

      // Add page.
      $expected = "/group/{$group->id()}/content/add";
      $this->assertEquals($expected, $item->toUrl('add-page')->toString());

      // Collection.
      $expected = "/group/{$group->id()}/content";
      $this->assertEquals($expected, $item->toUrl('collection')->toString());

      // Create form.
      $expected = "/group/{$group->id()}/content/create/group_membership?group_content={$item->id()}";
      $this->assertEquals($expected, $item->toUrl('create-form')->toString());

      // Create page.
      $expected = "/group/{$group->id()}/content/create?group_content={$item->id()}";
      $this->assertEquals($expected, $item->toUrl('create-page')->toString());

      // Delete form.
      $expected = "/group/{$group->id()}/content/{$item->id()}/delete";
      $this->assertEquals($expected, $item->toUrl('delete-form')->toString());

      // Edit form.
      $expected = "/group/{$group->id()}/content/{$item->id()}/edit";
      $this->assertEquals($expected, $item->toUrl('edit-form')->toString());
    }
  }

  /**
   * Tests that after adding an entity to a group, it gets saved again.
   *
   * @covers ::postSave
   *
   * @see group_test_user_update()
   */
  public function testSubjectResaved() {
    $changed = 123456789;
    $account = $this->createUser(['changed' => $changed]);

    $group = $this->createGroup();
    $group->addContent($account, 'group_membership');

    // All users whose changed time was set to 123456789 get their changed time
    // set to 530496000 in group_test_user_update() when the account is updated.
    $account_unchanged = $this->entityTypeManager->getStorage('user')->loadUnchanged($account->id());
    $this->assertEquals(530496000, $account_unchanged->getChangedTime(), 'Account was saved as part of being added to a group.');
  }

}
