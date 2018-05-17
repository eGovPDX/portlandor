<?php

namespace Drupal\group\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Provides a common interface for group content enabler managers.
 */
interface GroupContentEnablerManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface {

  /**
   * Returns a plugin collection of all available content enablers.
   *
   * This collection will not have anything set in the individual plugins'
   * configuration. Do not use any methods on the plugin that require a group
   * type to be set or you may encounter unexpected behavior. Instead, use
   * ::getInstalled() while providing a group type argument to get fully
   * configured instances of the plugins.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerCollection
   *   A plugin collection with a vanilla instance of every known plugin.
   */
  public function getAll();

  /**
   * Returns a plugin collection of all installed content enablers.
   *
   * Warning: When called without a $group_type argument, this will return a
   * collection of vanilla plugin instances. See ::getAll() for details about
   * vanilla instances.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to retrieve installed plugin for.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerCollection
   *   A plugin collection with a vanilla instance of every installed plugin. If
   *   $group_type was provided, the collection will contain fully instantiated
   *   plugins for the provided group type.
   */
  public function getInstalled(GroupTypeInterface $group_type = NULL);

  /**
   * Returns the plugin ID of all content enablers in use.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to retrieve plugin IDs for.
   *
   * @return string[]
   *   A list of all installed content enabler plugin IDs. If $group_type was
   *   provided, this will only return the installed IDs for that group type.
   */
  public function getInstalledIds(GroupTypeInterface $group_type = NULL);
  
  /**
   * Clears static and persistent installed plugin ID caches.
   * 
   * @deprecated in Group 1.0-beta3, will be removed before Group 1.0-rc1. Use
   *   ::clearCachedPluginMaps() instead.
   */
  public function clearCachedInstalledIds();

  /**
   * Installs all plugins which are marked as enforced.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to install enforced plugins on. Leave blank to
   *   run the installation process for all group types.
   */
  public function installEnforced(GroupTypeInterface $group_type = NULL);

  /**
   * Retrieves all of the group content type IDs for a content plugin.
   *
   * @param $plugin_id
   *   The ID of the plugin to retrieve group content type IDs for.
   *
   * @return string[]
   *   An array of group content type IDs.
   */
  public function getGroupContentTypeIds($plugin_id);

  /**
   * Retrieves a list of group content type IDs per plugin ID.
   *
   * @return array
   *   An array of group content type ID arrays, keyed by plugin ID.
   */
  public function getPluginGroupContentTypeMap();

  /**
   * Clears static and persistent group content type ID map caches.
   * 
   * @deprecated in Group 1.0-beta3, will be removed before Group 1.0-rc1. Use
   *   ::clearCachedPluginMaps() instead.
   */
  public function clearCachedGroupContentTypeIdMap();

  /**
   * Retrieves a list of plugin IDs per group type ID.
   *
   * @return array
   *   An array of content plugin ID arrays, keyed by group type ID.
   */
  public function getGroupTypePluginMap();

  /**
   * Clears the static per group type plugin collection cache.
   * 
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   (optional) The group type to clear the cache for. Leave blank to clear
   *   the cache for all group types.
   */
  public function clearCachedGroupTypeCollections(GroupTypeInterface $group_type = NULL);
  
  /**
   * Clears static and persistent plugin ID map caches.
   */
  public function clearCachedPluginMaps();

}
