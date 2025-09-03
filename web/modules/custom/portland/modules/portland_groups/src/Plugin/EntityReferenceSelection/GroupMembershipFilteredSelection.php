<?php

namespace Drupal\portland_groups\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * @EntityReferenceSelection(
 *   id = "default:group_membership_filter",
 *   label = @Translation("Filter by current user's group membership"),
 *   entity_types = {"group"},
 *   group = "default",
 *   weight = 1,
 * )
 */
class GroupMembershipFilteredSelection extends DefaultSelection
{

  /**
   * Override the entity query to filter groups by current user's group membership.
   * Used in custom/portland/modules/portland_groups/src/Plugin/Action/ChangeGroupOwnership.php
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS')
  {
    $query = parent::buildEntityQuery($match, $match_operator);
    $user = \Drupal::currentUser();
    if(in_array('administrator', $user->getRoles())) {
      // If the user is a site admin, allow them to select any group.
      return $query;
    }

    $group_membership_service = \Drupal::service('group.membership_loader');
    $group_memberships = $group_membership_service->loadByUser($user);
    $group_ids = array_map(function ($membership) {
      return $membership->getGroup()->id();
    }, $group_memberships);

    $query->condition('id', $group_ids, 'IN');
    return $query;
  }
}
