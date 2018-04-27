<?php

namespace Drupal\Tests\group\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for Group functional tests.
 */
abstract class GroupBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group', 'group_test_config'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A test user with group creation rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupCreator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->groupCreator = $this->drupalCreateUser($this->getGlobalPermissions());
    $this->drupalLogin($this->groupCreator);
  }

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'create default group',
      'create other group',
    ];
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

}
