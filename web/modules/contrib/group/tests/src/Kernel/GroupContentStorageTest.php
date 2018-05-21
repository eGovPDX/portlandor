<?php

namespace Drupal\Tests\group\Kernel;

/**
 * Tests the behavior of group content storage handler.
 *
 * @coversDefaultClass \Drupal\group\Entity\Storage\GroupContentStorage
 * @group group
 */
class GroupContentStorageTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin'];

  /**
   * The group content storage handler.
   *
   * @var \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->storage = $this->entityTypeManager->getStorage('group_content');

    // Enable the test plugins on the default group type.
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $group_type = $this->entityTypeManager->getStorage('group_type')->load('default');
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $storage->createFromPlugin($group_type, 'user_as_content')->save();
    $storage->createFromPlugin($group_type, 'group_as_content')->save();
  }

  /**
   * Creates an unsaved group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createUnsavedGroup($values = []) {
    $group = $this->entityTypeManager->getStorage('group')->create($values + [
      'type' => 'default',
      'label' => $this->randomMachineName(),
    ]);
    return $group;
  }

  /**
   * Creates an unsaved user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUnsavedUser($values = []) {
    $account = $this->entityTypeManager->getStorage('user')->create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    return $account;
  }

  /**
   * Tests the creation of a GroupContent entity using an unsaved group.
   *
   * @covers ::createForEntityInGroup
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   * @expectedExceptionMessage Cannot add an entity to an unsaved group.
   */
  public function testCreateForUnsavedGroup() {
    $group = $this->createUnsavedGroup();
    $account = $this->createUser();
    $this->storage->createForEntityInGroup($account, $group, 'user_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using an unsaved entity.
   *
   * @covers ::createForEntityInGroup
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   * @expectedExceptionMessage Cannot add an unsaved entity to a group.
   */
  public function testCreateForUnsavedEntity() {
    $group = $this->createGroup();
    $account = $this->createUnsavedUser();
    $this->storage->createForEntityInGroup($account, $group, 'user_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using an incorrect plugin ID.
   *
   * @covers ::createForEntityInGroup
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   * @expectedExceptionMessage Invalid plugin provided for adding the entity to the group.
   */
  public function testCreateForInvalidPluginId() {
    $group = $this->createGroup();
    $account = $this->createUser();
    $this->storage->createForEntityInGroup($account, $group, 'group_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using an incorrect bundle.
   *
   * @covers ::createForEntityInGroup
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   * @expectedExceptionMessage The provided plugin provided does not support the entity's bundle.
   */
  public function testCreateForInvalidBundle() {
    $group = $this->createGroup();
    $subgroup = $this->createGroup(['type' => 'other']);
    $this->storage->createForEntityInGroup($subgroup, $group, 'group_as_content');
  }

  /**
   * Tests the creation of a GroupContent entity using a bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateWithBundle() {
    $group = $this->createGroup();
    $subgroup = $this->createGroup();
    $group_content = $this->storage->createForEntityInGroup($subgroup, $group, 'group_as_content');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupContentInterface', $group_content, 'Created a GroupContent entity using a bundle-specific plugin.');
  }

  /**
   * Tests the creation of a GroupContent entity using no bundle.
   *
   * @covers ::createForEntityInGroup
   */
  public function testCreateWithoutBundle() {
    $group = $this->createGroup();
    $account = $this->createUser();
    $group_content = $this->storage->createForEntityInGroup($account, $group, 'user_as_content');
    $this->assertInstanceOf('\Drupal\group\Entity\GroupContentInterface', $group_content, 'Created a GroupContent entity using a bundle-independent plugin.');
  }

  /**
   * Tests the loading of GroupContent entities for an unsaved group.
   *
   * @covers ::loadByGroup
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   * @expectedExceptionMessage Cannot load GroupContent entities for an unsaved group.
   */
  public function testLoadByUnsavedGroup() {
    $group = $this->createUnsavedGroup();
    $this->storage->loadByGroup($group);
  }

  /**
   * Tests the loading of GroupContent entities for a group.
   *
   * @covers ::loadByGroup
   */
  public function testLoadByGroup() {
    $group = $this->createGroup();
    $this->assertCount(1, $this->storage->loadByGroup($group), 'Managed to load the group creator membership by group.');
  }

  /**
   * Tests the loading of GroupContent entities for an unsaved entity.
   *
   * @covers ::loadByEntity
   * @expectedException \Drupal\Core\Entity\EntityStorageException
   * @expectedExceptionMessage Cannot load GroupContent entities for an unsaved entity.
   */
  public function testLoadByUnsavedEntity() {
    $group = $this->createUnsavedGroup();
    $this->storage->loadByEntity($group);
  }

  /**
   * Tests the loading of GroupContent entities for an entity.
   *
   * @covers ::loadByEntity
   */
  public function testLoadByEntity() {
    $this->createGroup();
    $account = $this->getCurrentUser();
    $this->assertCount(1, $this->storage->loadByEntity($account), 'Managed to load the group creator membership by user.');
  }

  /**
   * Tests the loading of GroupContent entities for an entity.
   *
   * @covers ::loadByContentPluginId
   */
  public function testLoadByContentPluginId() {
    $this->createGroup();
    $this->assertCount(1, $this->storage->loadByContentPluginId('group_membership'), 'Managed to load the group creator membership by plugin ID.');
  }

}
