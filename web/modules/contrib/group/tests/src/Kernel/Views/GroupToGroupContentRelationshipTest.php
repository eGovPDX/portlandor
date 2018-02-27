<?php

namespace Drupal\Tests\group\Kernel\Views;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the group_to_group_content relationship handler.
 *
 * @see \Drupal\group\Plugin\views\relationship\GroupToGroupContent
 *
 * @group group
 */
class GroupToGroupContentRelationshipTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group', 'field', 'text', 'group_test_config', 'user', 'group_test_plugin', 'group_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_to_group_content_relationship'];

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
    $this->installTestConfiguration();
    $this->setCurrentUser($this->createUser());

    // Enable the 'user_as_content' plugin on the 'default' group type.
    $group_type = $this->entityTypeManager->getStorage('group_type')->load('default');
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($group_type, 'user_as_content')->save();

    ViewTestData::createTestViews(get_class($this), ['group_test_views']);
  }

  /**
   * Installs the required configuration and schemas for this test.
   */
  protected function installTestConfiguration() {
    $this->installConfig(['group', 'field', 'group_test_config']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');
  }

  /**
   * Set the current user so group creation can rely on it.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to set as the current user.
   */
  protected function setCurrentUser(AccountInterface $account) {
    $this->container->get('current_user')->setAccount($account);
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
    $view = Views::getView('test_group_to_group_content_relationship');
    $view->setDisplay();

    if ($view->preview()) {
      return $view->result;
    }

    return [];
  }

  /**
   * Tests that a group's owner (default member) is returned by the view.
   */
  public function testGroupOwnerIsListed() {
    $this->assertEquals(0, count($this->getViewResults()), 'The view displays no members.');
    $this->createGroup();
    $this->assertEquals(1, count($this->getViewResults()), 'The view displays the default member.');
  }

  /**
   * Tests that an extra group member is returned by the view.
   *
   * @depends testGroupOwnerIsListed
   */
  public function testAddedMemberIsListed() {
    $group = $this->createGroup();
    $group->addMember($this->createUser());
    $this->assertEquals(2, count($this->getViewResults()), 'The view displays both the default and the added member.');
  }

  /**
   * Tests that any other group content is not returned by the view.
   *
   * @depends testGroupOwnerIsListed
   */
  public function testOtherContentIsNotListed() {
    $group = $this->createGroup();
    $group->addContent($this->createUser(), 'user_as_content');
    $this->assertEquals(1, count($this->getViewResults()), 'The view only displays the default member and not the user that was added as content.');
  }

}
