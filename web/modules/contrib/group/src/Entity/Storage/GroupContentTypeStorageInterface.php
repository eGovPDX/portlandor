<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Defines an interface for group content type entity storage classes.
 */
interface GroupContentTypeStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Retrieves all group content types for a group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to load the group content types for.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   An array of group content types indexed by their IDs.
   */
  public function loadByGroupType(GroupTypeInterface $group_type);

  /**
   * Retrieves group content types by their responsible plugin ID.
   *
   * @param string|string[] $plugin_id
   *   The ID of the content enabler plugin or an array of plugin IDs. If more
   *   than one plugin ID is provided, this will load all of the group content
   *   types that match any of the provided plugin IDs.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   An array of group content types indexed by their IDs.
   */
  public function loadByContentPluginId($plugin_id);

  /**
   * Retrieves group content types which could serve a given entity type.
   *
   * @param string $entity_type_id
   *   An entity type ID which may be served by one or more group content types.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   An array of group content types indexed by their IDs.
   */
  public function loadByEntityTypeId($entity_type_id);

  /**
   * Creates a group content type for a group type using a specific plugin.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to create the group content type for.
   * @param string $plugin_id
   *   The ID of the content enabler plugin to use.
   * @param array $configuration
   *   (optional) An array of content enabler plugin configuration.
   * 
   * @return \Drupal\group\Entity\GroupContentTypeInterface
   *   A new, unsaved GroupContentType entity.
   */
  public function createFromPlugin(GroupTypeInterface $group_type, $plugin_id, array $configuration = []);

}
