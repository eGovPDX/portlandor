<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines an interface for group content entity storage classes.
 */
interface GroupContentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Creates a GroupContent entity for placing a content entity in a group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to add to the group.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the content entity to.
   * @param string $plugin_id
   *   The ID of the content enabler plugin to add the entity with.
   * @param array $values
   *   (optional) Extra values to add to the GroupContent entity.
   *
   * @return \Drupal\group\Entity\GroupContentInterface
   *   A new GroupContent entity.
   */
  public function createForEntityInGroup(ContentEntityInterface $entity, GroupInterface $group, $plugin_id, $values = []);

  /**
   * Retrieves all GroupContent entities for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to load the group content entities for.
   * @param string $plugin_id
   *   (optional) A content enabler plugin ID to filter on.
   * @param array $filters
   *   (optional) An associative array of extra filters where the keys are
   *   property or field names and the values are the value to filter on.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   A list of GroupContent entities matching the criteria.
   */
  public function loadByGroup(GroupInterface $group, $plugin_id = NULL, $filters = []);

  /**
   * Retrieves all GroupContent entities that represent a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity which may be within one or more groups.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   A list of GroupContent entities which refer to the given entity.
   */
  public function loadByEntity(ContentEntityInterface $entity);

  /**
   * Retrieves all GroupContent entities by their responsible plugin ID.
   *
   * @param string $plugin_id
   *   The ID of the content enabler plugin.
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   *   A list of GroupContent entities indexed by their IDs.
   */
  public function loadByContentPluginId($plugin_id);

}
