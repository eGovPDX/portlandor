<?php

namespace Drupal\group;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Loader for wrapped GroupContent entities using the 'group_membership' plugin.
 *
 * Seeing as this class is part of the main module, we could have easily put its
 * functionality in GroupContentStorage. We chose not to because other modules
 * won't have that power and we should provide them with an example of how to
 * write such a plugin-specific GroupContent loader.
 *
 * Also note that we don't simply return GroupContent entities, but wrapped
 * copies of said entities, namely \Drupal\group\GroupMembership. In a future
 * version we should investigate the feasibility of extending GroupContent
 * entities rather than wrapping them.
 */
class GroupMembershipLoader implements GroupMembershipLoaderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user's account object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new GroupTypeController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Gets the group content storage.
   *
   * @return \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected function groupContentStorage() {
    return $this->entityTypeManager->getStorage('group_content');
  }

  /**
   * Wraps GroupContent entities in a GroupMembership object.
   *
   * @param \Drupal\group\Entity\GroupContentInterface[] $entities
   *   An array of GroupContent entities to wrap.
   *
   * @return \Drupal\group\GroupMembership[]
   *   A list of GroupMembership wrapper objects.
   */
  protected function wrapGroupContentEntities($entities) {
    $group_memberships = [];
    foreach ($entities as $group_content) {
      $group_memberships[] = new GroupMembership($group_content);
    }
    return $group_memberships;
  }

  /**
   * {@inheritdoc}
   */
  public function load(GroupInterface $group, AccountInterface $account) {
    $filters = ['entity_id' => $account->id()];
    $group_contents = $this->groupContentStorage()->loadByGroup($group, 'group_membership', $filters);
    $group_memberships = $this->wrapGroupContentEntities($group_contents);
    return $group_memberships ? reset($group_memberships) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $roles = NULL) {
    $filters = [];

    if (isset($roles)) {
      $filters['group_roles'] = (array) $roles;
    }

    $group_contents = $this->groupContentStorage()->loadByGroup($group, 'group_membership', $filters);
    return $this->wrapGroupContentEntities($group_contents);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUser(AccountInterface $account = NULL, $roles = NULL) {
    if (!isset($account)) {
      $account = $this->currentUser;
    }

    // Load all group content types for the membership content enabler plugin.
    $group_content_types = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->loadByProperties(['content_plugin' => 'group_membership']);

    // If none were found, there can be no memberships either.
    if (empty($group_content_types)) {
      return [];
    }

    // Try to load all possible membership group content for the user.
    $group_content_type_ids = [];
    foreach ($group_content_types as $group_content_type) {
      $group_content_type_ids[] = $group_content_type->id();
    }

    $properties = ['type' => $group_content_type_ids, 'entity_id' => $account->id()];
    if (isset($roles)) {
      $properties['group_roles'] = (array) $roles;
    }

    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = $this->groupContentStorage()->loadByProperties($properties);
    return $this->wrapGroupContentEntities($group_contents);
  }

}
