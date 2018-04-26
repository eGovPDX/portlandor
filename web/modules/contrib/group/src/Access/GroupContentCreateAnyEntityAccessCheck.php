<?php

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for group content target entity creation.
 */
class GroupContentCreateAnyEntityAccessCheck implements AccessInterface {

  /**
   * Checks access for group content target entity creation routes.
   *
   * All routes using this access check should have a group parameter and have
   * the _group_content_create_any_entity_access requirement set to 'TRUE' or
   * 'FALSE'.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group in which the content should be created.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group) {
    $needs_access = $route->getRequirement('_group_content_create_any_entity_access') === 'TRUE';

    // Retrieve all of the group content plugins for the group.
    $plugins = $group->getGroupType()->getInstalledContentPlugins();

    // Find out which ones allow the user to create a target entity.
    foreach ($plugins as $plugin) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      if ($plugin->createEntityAccess($group, $account)->isAllowed()) {
        // Allow access if the route flag was set to 'TRUE'.
        return AccessResult::allowedIf($needs_access);
      }
    }

    // If we got this far, it means the user could not create any content in the
    // group. So only allow access if the route flag was set to 'FALSE'.
    return AccessResult::allowedIf(!$needs_access);
  }

}
