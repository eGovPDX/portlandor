<?php

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for group content target entity creation.
 */
class GroupContentCreateEntityAccessCheck implements AccessInterface {

  /**
   * Checks access for group content target entity creation routes.
   *
   * All routes using this access check should have a group and plugin_id
   * parameter and have the _group_content_create_entity_access requirement set
   * to either 'TRUE' or 'FALSE'.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group in which the content should be created.
   * @param string $plugin_id
   *   The group content enabler ID to use for the group content entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group, $plugin_id) {
    $needs_access = $route->getRequirement('_group_content_create_entity_access') === 'TRUE';

    // We can only get the group content type ID if the plugin is installed.
    if (!$group->getGroupType()->hasContentPlugin($plugin_id)) {
      return AccessResult::neutral();
    }

    // Determine whether the user can create entities of the provided type.
    $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
    $access = $plugin->createEntityAccess($group, $account)->isAllowed();

    // Only allow access if the user can create group content target entities
    // using the provided plugin or if he doesn't need access to do so.
    return AccessResult::allowedIf($access xor !$needs_access);
  }

}
