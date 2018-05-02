<?php

namespace Drupal\group\Access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\group\Entity\GroupInterface;

/**
 * Generates and caches the permissions hash for a group membership.
 */
class GroupPermissionsHashGenerator implements GroupPermissionsHashGeneratorInterface {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The cache backend interface to use for the persistent cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache backend interface to use for the static cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $static;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a GroupPermissionsHashGenerator object.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend interface to use for the persistent cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface
   *   The cache backend interface to use for the static cache.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(PrivateKey $private_key, CacheBackendInterface $cache, CacheBackendInterface $static, EntityTypeManagerInterface $entity_type_manager) {
    $this->privateKey = $private_key;
    $this->cache = $cache;
    $this->static = $static;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * Cached by role, invalidated whenever permissions change.
   */
  public function generate(GroupInterface $group, AccountInterface $account) {
    // If the user can bypass group access we return a unique hash.
    if ($account->hasPermission('bypass group access')) {
      return $this->hash('bypass-group-access');
    }

    // Retrieve all of the group roles the user may get for the group.
    $group_roles = $this->groupRoleStorage()->loadByUserAndGroup($account, $group);

    // Sort the group roles by ID.
    ksort($group_roles);

    // Create a cache ID based on the role IDs.
    $role_list = implode(',', array_keys($group_roles));
    $cid = "group_permissions_hash:$role_list";

    // Retrieve the hash from the static cache if available.
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }
    else {
      // Build cache tags for the individual group roles.
      $tags = Cache::buildTags('config:group.role', array_keys($group_roles), '.');

      // Retrieve the hash from the persistent cache if available.
      if ($cache = $this->cache->get($cid)) {
        $permissions_hash = $cache->data;
      }
      // Otherwise generate the hash and store it in the persistent cache.
      else {
        $permissions_hash = $this->doGenerate($group_roles);
        $this->cache->set($cid, $permissions_hash, Cache::PERMANENT, $tags);
      }

      // Store the hash in the static cache.
      $this->static->set($cid, $permissions_hash, Cache::PERMANENT, $tags);
    }

    return $permissions_hash;
  }

  /**
   * Generates a hash that uniquely identifies the group member's permissions.
   *
   * @param \Drupal\group\Entity\GroupRoleInterface[] $group_roles
   *   The group roles to generate the permission hash for.
   *
   * @return string
   *   The permissions hash.
   */
  protected function doGenerate(array $group_roles) {
    $permissions = [];
    foreach ($group_roles as $group_role) {
      $permissions = array_merge($permissions, $group_role->getPermissions());
    }
    return $this->hash(serialize(array_unique($permissions)));
  }

  /**
   * Hashes the given string.
   *
   * @param string $identifier
   *   The string to be hashed.
   *
   * @return string
   *   The hash.
   */
  protected function hash($identifier) {
    return hash('sha256', $this->privateKey->get() . Settings::getHashSalt() . $identifier);
  }

  /**
   * Gets the group role storage.
   *
   * @return \Drupal\group\Entity\Storage\GroupRoleStorageInterface
   */
  protected function groupRoleStorage() {
    return $this->entityTypeManager->getStorage('group_role');
  }

}
