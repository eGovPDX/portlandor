<?php

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface defining a group content type entity.
 */
interface GroupContentTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Gets the group type the content type was created for.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type for which the content type was created.
   */
  public function getGroupType();

  /**
   * Gets the group type ID the content type was created for.
   *
   * @return string
   *   The group type ID for which the content type was created.
   */
  public function getGroupTypeId();

  /**
   * Gets the content enabler plugin the content type uses.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The content enabler plugin the content type uses.
   */
  public function getContentPlugin();

  /**
   * Gets the content enabler plugin ID the content type uses.
   *
   * @return string
   *   The content enabler plugin ID the content type uses.
   */
  public function getContentPluginId();

  /**
   * Updates the configuration of the content enabler plugin.
   * 
   * Any keys that were left out will be reset to the default.
   *
   * @param array $configuration
   *   An array of content enabler plugin configuration.
   */
  public function updateContentPlugin(array $configuration);

  /**
   * Loads group content type entities by their responsible plugin ID.
   *
   * @param string|string[] $plugin_id
   *   The ID of the content enabler plugin or an array of plugin IDs. If more
   *   than one plugin ID is provided, this will load all of the group content
   *   types that match any of the provided plugin IDs.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   An array of group content type entities indexed by their IDs.
   */
  public static function loadByContentPluginId($plugin_id);

  /**
   * Loads group content type entities which could serve a given entity type.
   *
   * @param string $entity_type_id
   *   An entity type ID which may be served by one or more group content types.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   An array of group content type entities which serve the given entity.
   */
  public static function loadByEntityTypeId($entity_type_id);
  
}
