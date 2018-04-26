<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\group\GroupRoleSynchronizer;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;

/**
 * Tests the outsider group role synchronizer service.
 *
 * @coversDefaultClass \Drupal\group\GroupRoleSynchronizer
 * @group group
 */
class GroupRoleSynchronizerTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The group role synchronizer service.
   *
   * @var \Drupal\group\GroupRoleSynchronizer
   */
  protected $groupRoleSynchronizer;

  public function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->groupRoleSynchronizer = new GroupRoleSynchronizer($this->entityTypeManager->reveal());
  }

  /**
   * @covers ::getGroupRoleId
   */
  public function testGetGroupRoleId() {
    $expected = 'foo-' . substr(md5('group_role_sync.bar'), 0, 9);
    $this->assertEquals($expected, $this->groupRoleSynchronizer->getGroupRoleId('foo', 'bar'));
  }

  /**
   * @covers ::getGroupRoleIdsByGroupType
   * @depends testGetGroupRoleId
   */
  public function testGetGroupRoleIdsByGroupType() {
    $this->setUpConfigEntityStorage('user_role', ['bar', 'baz']);
    $expected = [
      $this->groupRoleSynchronizer->getGroupRoleId('foo', 'bar'),
      $this->groupRoleSynchronizer->getGroupRoleId('foo', 'baz'),
    ];
    $this->assertEquals($expected, $this->groupRoleSynchronizer->getGroupRoleIdsByGroupType('foo'));
  }

  /**
   * @covers ::getGroupRoleIdsByUserRole
   * @depends testGetGroupRoleId
   */
  public function testGetGroupRoleIdsByUserRole() {
    $this->setUpConfigEntityStorage('group_type', ['foo', 'bar']);
    $expected = [
      $this->groupRoleSynchronizer->getGroupRoleId('foo', 'baz'),
      $this->groupRoleSynchronizer->getGroupRoleId('bar', 'baz'),
    ];
    $this->assertEquals($expected, $this->groupRoleSynchronizer->getGroupRoleIdsByUserRole('baz'));
  }

  /**
   * @covers ::createGroupRoles
   * @depends testGetGroupRoleId
   */
  public function testCreateGroupRoles() {
    // Set up the global 'anonymous', 'authenticated' and 'baz' roles.
    $user_role_1 = $this->prophesize(RoleInterface::class);
    $user_role_2 = $this->prophesize(RoleInterface::class);
    $user_role_3 = $this->prophesize(RoleInterface::class);
    $user_role_3->id()->willReturn('baz');
    $user_role_3->label()->willReturn('Custom user role');
    $user_role_3->getWeight()->willReturn(2);
    $user_role_3->getConfigDependencyName()->willReturn('user.role.baz');
    $user_roles = [
      'anonymous' => $user_role_1->reveal(),
      'authenticated' => $user_role_2->reveal(),
      'baz' => $user_role_3->reveal(),
    ];

    // Set up the group type and user role storage.
    $this->setUpConfigEntityStorage('group_type', ['foo', 'bar']);
    $this->setUpConfigEntityStorage('user_role', [], $user_roles);

    $storage = $this->prophesize(ConfigEntityStorageInterface::class);
    $query = $this->prophesize(QueryInterface::class);

    // Test whether two IDs will be checked against the database.
    $expected_ids = [
      $this->groupRoleSynchronizer->getGroupRoleId('foo', 'baz'),
      $this->groupRoleSynchronizer->getGroupRoleId('bar', 'baz'),
    ];
    $query->condition('id', $expected_ids)->shouldBeCalledTimes(1);

    // Return the ID for the bar-baz combo so only foo-bar will be created.
    $returned_id = $this->groupRoleSynchronizer->getGroupRoleId('bar', 'baz');
    $query->execute()->willReturn([$returned_id => $returned_id]);
    $storage->getQuery()->willReturn($query->reveal());

    // Check for the generated group role definition and whether it's created.
    $group_role = $this->prophesize(GroupRoleInterface::class);
    $group_role->save()->shouldbeCalledTimes(1);
    $definition = [
      'id' => $this->groupRoleSynchronizer->getGroupRoleId('foo', 'baz'),
      'label' => 'Custom user role',
      'weight' => 2,
      'internal' => TRUE,
      'audience' => 'outsider',
      'group_type' => 'foo',
      'permissions_ui' => FALSE,
      'dependencies' => [
        'enforced' => [
          'config' => ['user.role.baz'],
        ],
      ],
    ];
    $storage->create($definition)->shouldBeCalledTimes(1)->willReturn($group_role->reveal());

    // Set the prophesized group role storage and run our code.
    $this->entityTypeManager->getStorage('group_role')->willReturn($storage->reveal());
    $this->groupRoleSynchronizer->createGroupRoles();
  }

  /**
   * @covers ::updateGroupRoleLabels
   * @depends testGetGroupRoleId
   */
  public function testUpdateGroupRoleLabels() {
    // Set up the global role.
    $user_role = $this->prophesize(RoleInterface::class);
    $user_role->id()->willReturn('bar');
    $user_role->label()->willReturn('Baz');

    // Set up the corresponding group role.
    $group_role = $this->prophesize(GroupRoleInterface::class);
    $group_role->save()->shouldBeCalledTimes(1);
    $group_role->set('label', 'Baz')->shouldBeCalledTimes(1)->willReturn($group_role->reveal());
    $group_roles = [$this->groupRoleSynchronizer->getGroupRoleId('foo', 'bar') => $group_role->reveal()];

    // Set up the group type and group role storage.
    $this->setUpConfigEntityStorage('group_type', ['foo']);
    $this->setUpConfigEntityStorage('group_role', [], $group_roles);

    // See whether the label of the group role got updated and saved.
    $this->groupRoleSynchronizer->updateGroupRoleLabels($user_role->reveal());
  }

  /**
   * Mock and set up a config entity type's storage handler.
   *
   * @param string $entity_type_id
   *   The ID of the config entity type to mock the storage for.
   * @param string[] $entity_ids
   *   The IDs of the config entities to return from an entity query.
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface[] $entities
   *   The config entities to return from a loadMultiple() call.
   */
  protected function setUpConfigEntityStorage($entity_type_id, $entity_ids = [], $entities = []) {
    $storage = $this->prophesize(ConfigEntityStorageInterface::class);

    if (!empty($entity_ids)) {
      $query = $this->prophesize(QueryInterface::class);
      $query->execute()->willReturn($entity_ids);
      $storage->getQuery()->willReturn($query->reveal());
    }

    if (!empty($entities)) {
      $storage->loadMultiple(NULL)->willReturn($entities);
      $storage->loadMultiple(array_keys($entities))->willReturn($entities);
    }

    $this->entityTypeManager->getStorage($entity_type_id)->willReturn($storage->reveal());
  }

}
