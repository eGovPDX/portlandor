<?php

namespace Drupal\Tests\group\Kernel\Views;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the group_content_to_entity relationship handler.
 *
 * @see \Drupal\group\Plugin\views\relationship\GroupContentToEntity
 *
 * @group group
 */
class GroupContentToEntityRelationshipTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group', 'field', 'text', 'group_test_config', 'user', 'group_test_plugin', 'group_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_content_to_entity_relationship'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installConfig(['group', 'field', 'group_test_config']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');

    // Set the current user so group creation can rely on it.
    $this->container->get('current_user')->setAccount($this->createUser());

    // Enable the user_as_content plugin on the default group type.
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $group_type = $this->entityTypeManager->getStorage('group_type')->load('default');
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($group_type, 'user_as_content')->save();

    ViewTestData::createTestViews(get_class($this), ['group_test_views']);
  }

  /**
   * Creates a group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createGroup($values = []) {
    $group = $this->entityTypeManager->getStorage('group')->create($values + [
      'type' => 'default',
      'label' => $this->randomMachineName(),
    ]);
    $group->enforceIsNew();
    $group->save();
    return $group;
  }

  /**
   * Creates a user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUser($values = []) {
    $account = $this->entityTypeManager->getStorage('user')->create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $account->enforceIsNew();
    $account->save();
    return $account;
  }

  /**
   * Retrieves the results for this test's view.
   *
   * @return \Drupal\views\ResultRow[]
   *   A list of view results.
   */
  protected function getViewResults() {
    $view = Views::getView(reset($this::$testViews));
    $view->setDisplay();

    if ($view->preview()) {
      return $view->result;
    }

    return [];
  }

  /**
   * Tests that a regular user is not returned by the view.
   */
  public function testRegularUserIsNotListed() {
    $this->createUser();
    $this->assertEquals(0, count($this->getViewResults()), 'The view does not show regular users.');
  }

  /**
   * Tests that a group's owner (default member) is returned by the view.
   */
  public function testGroupOwnerIsListed() {
    $this->createGroup();
    $this->assertEquals(1, count($this->getViewResults()), 'The view displays the user for the default member.');
  }

  /**
   * Tests that an extra group member is returned by the view.
   *
   * @depends testGroupOwnerIsListed
   */
  public function testAddedMemberIsListed() {
    $group = $this->createGroup();
    $group->addMember($this->createUser());
    $this->assertEquals(2, count($this->getViewResults()), 'The view displays the users for both the default and the added member.');
  }

  /**
   * Tests that any other group content is not returned by the view.
   *
   * @depends testGroupOwnerIsListed
   */
  public function testOtherContentIsNotListed() {
    $group = $this->createGroup();
    $group->addContent($this->createUser(), 'user_as_content');
    $this->assertEquals(1, count($this->getViewResults()), 'The view only displays the user for default member and not the one that was added as content.');
  }

}
