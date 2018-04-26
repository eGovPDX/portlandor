<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\GroupRoleSynchronizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for group role entities.
 *
 * This extends the base storage class, adding required special handling for
 * loading group role entities based on user and group information.
 */
class GroupRoleStorage extends ConfigEntityStorage implements GroupRoleStorageInterface {

  /**
   * Static cache of a user's group role IDs.
   *
   * @var array
   */
  protected $userGroupRoleIds = [];

  /**
   * The group role synchronizer service.
   *
   * @var \Drupal\group\GroupRoleSynchronizerInterface
   */
  protected $groupRoleSynchronizer;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * Constructs a GroupRoleStorage object.
   *
   * @param \Drupal\group\GroupRoleSynchronizerInterface $group_role_synchronizer
   *   The group role synchronizer service.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(GroupRoleSynchronizerInterface $group_role_synchronizer, GroupMembershipLoaderInterface $group_membership_loader, EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->groupRoleSynchronizer = $group_role_synchronizer;
    $this->groupMembershipLoader = $group_membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('group_role.synchronizer'),
      $container->get('group.membership_loader'),
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUserAndGroup(AccountInterface $account, GroupInterface $group, $include_implied = TRUE) {
    $uid = $account->id();
    $gid = $group->id();
    $key = $include_implied ? 'include' : 'exclude';

    if (!isset($this->userGroupRoleIds[$uid][$gid][$key])) {
      $ids = [];

      // Get the IDs from the 'group_roles' field, without loading the roles.
      if ($membership = $this->groupMembershipLoader->load($group, $account)) {
        foreach ($membership->getGroupContent()->group_roles as $group_role_ref) {
          $ids[] = $group_role_ref->target_id;
        }
      }

      // Add the implied group role IDs.
      if ($include_implied) {
        $group_type = $group->getGroupType();

        if ($membership !== FALSE) {
          $ids[] = $group_type->getMemberRoleId();
        }
        elseif ($account->isAnonymous()) {
          $ids[] = $group_type->getAnonymousRoleId();
        }
        else {
          $ids[] = $group_type->getOutsiderRoleId();
          foreach ($account->getRoles(TRUE) as $role_id) {
            $ids[] = $this->groupRoleSynchronizer->getGroupRoleId($group_type->id(), $role_id);
          }
        }
      }

      $this->userGroupRoleIds[$uid][$gid][$key] = $ids;
    }

    return $this->loadMultiple($this->userGroupRoleIds[$uid][$gid][$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function resetUserGroupRoleCache(AccountInterface $account, GroupInterface $group = NULL) {
    $uid = $account->id();
    if (isset($group)) {
      unset($this->userGroupRoleIds[$uid][$group->id()]);
    }
    else {
      unset($this->userGroupRoleIds[$uid]);
    }
  }

}
