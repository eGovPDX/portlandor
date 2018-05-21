<?php

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for group content creation.
 */
class GroupContentCreateAnyAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access for group content creation routes.
   *
   * All routes using this access check should have a group parameter and have
   * the _group_content_create_any_access requirement set to 'TRUE' or 'FALSE'.
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
    $needs_access = $route->getRequirement('_group_content_create_any_access') === 'TRUE';

    // Get the group content access control handler.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_content');

    // Retrieve all of the group content type IDs for the group.
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $entity_query = $storage->getQuery();
    $entity_query->condition('group_type', $group->bundle());
    $group_content_type_ids = $entity_query->execute();

    // Find out which ones the user has access to create.
    foreach ($group_content_type_ids as $group_content_type_id) {
      if ($access_control_handler->createAccess($group_content_type_id, $account, ['group' => $group])) {
        // Allow access if the route flag was set to 'TRUE'.
        return AccessResult::allowedIf($needs_access);
      }
    }

    // If we got this far, it means the user could not create any content in the
    // group. So only allow access if the route flag was set to 'FALSE'.
    return AccessResult::allowedIf(!$needs_access);
  }

}
