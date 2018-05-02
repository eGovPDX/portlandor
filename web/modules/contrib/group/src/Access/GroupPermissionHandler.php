<?php

namespace Drupal\group\Access;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;

/**
 * Provides the available permissions based on yml files.
 *
 * To define permissions you can use a $module.group.permissions.yml file. This
 * file defines machine names and human-readable names for each permission. The
 * machine names are the canonical way to refer to permissions for access
 * checking. Each permission may also have a restrict access and/or warning
 * message, keys defining what roles it applies to and a description.
 *
 * If your module needs to define dynamic permissions you can use the
 * permission_callbacks key to declare a callable that will return an array of
 * permissions, keyed by machine name. Each item in the array can contain the
 * same keys as an entry in $module.group.permissions.yml.
 *
 * To find a list of supported permission keys, have a look at the documentation
 * of GroupPermissionHandlerInterface::getPermissions().
 *
 * Here is an example from the group module itself (comments have been added):
 * @code
 * # The key is the permission machine name, and is required.
 * edit group:
 *   # (required) Human readable name of the permission used in the UI.
 *   title: 'Edit group'
 *   description: 'Edit the group information'
 *
 * # An array of callables used to generate dynamic permissions.
 * permission_callbacks:
 *   # Each item in the array should return an associative array with one or
 *   # more permissions following the same keys as the permission defined above.
 *   - Drupal\my_module\MyModuleGroupPermissions::permissions
 * @endcode
 *
 * @see group.group.permissions.yml
 * @see \Drupal\group\Access\GroupPermissionHandlerInterface::getPermissions()
 */
class GroupPermissionHandler implements GroupPermissionHandlerInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The YAML discovery class to find all .group.permissions.yml files.
   *
   * @var \Drupal\Component\Discovery\YamlDiscovery
   */
  protected $yamlDiscovery;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The group content enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new PermissionHandler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TranslationInterface $string_translation, ControllerResolverInterface $controller_resolver, GroupContentEnablerManagerInterface $plugin_manager) {
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
    $this->controllerResolver = $controller_resolver;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * Gets the YAML discovery.
   *
   * @return \Drupal\Component\Discovery\YamlDiscovery
   *   The YAML discovery.
   */
  protected function getYamlDiscovery() {
    if (!isset($this->yamlDiscovery)) {
      $this->yamlDiscovery = new YamlDiscovery('group.permissions', $this->moduleHandler->getModuleDirectories());
    }
    return $this->yamlDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions($include_plugins = FALSE) {
    $all_permissions = $this->buildPermissionsYaml();

    // Add the plugin defined permissions to the whole. We query all defined
    // plugins to avoid scenarios where modules want to ship with default
    // configuration but can't because their plugins may not be installed along
    // with the module itself (i.e.: non-enforced plugins).
    if ($include_plugins) {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      foreach ($this->pluginManager->getAll() as $plugin) {
        $provider = $plugin->getProvider();
        $section = $plugin->getLabel()->__toString();

        foreach ($plugin->getPermissions() as $permission_name => $permission) {
          $permission += ['provider' => $provider, 'section' => $section];
          $all_permissions[$permission_name] = $this->completePermission($permission);
        }
      }
    }

    return $this->sortPermissions($all_permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissionsByGroupType(GroupTypeInterface $group_type) {
    $all_permissions = $this->buildPermissionsYaml();

    // Add the plugin defined permissions to the whole.
    foreach ($group_type->getInstalledContentPlugins() as $plugin) {
      $provider = $plugin->getProvider();
      $section = $plugin->getLabel()->__toString();

      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
      foreach ($plugin->getPermissions() as $permission_name => $permission) {
        $permission += ['provider' => $provider, 'section' => $section];
        $all_permissions[$permission_name] = $this->completePermission($permission);
      }
    }

    return $this->sortPermissions($all_permissions);
  }

  /**
   * Completes a permission by adding in defaults and translating its strings.
   *
   * @param array $permission
   *   The raw permission to complete.
   *
   * @return array
   *   A permission which is guaranteed to have all the required keys set.
   */
  protected function completePermission($permission) {
    $permission += [
      'title_args' => [],
      'description' => '',
      'description_args' => [],
      'restrict access' => FALSE,
      'warning' => !empty($permission['restrict access']) ? 'Warning: Give to trusted roles only; this permission has security implications.' : '',
      'warning_args' => [],
      'allowed for' => ['anonymous', 'outsider', 'member'],
    ];

    // Translate the title and optionally the description and warning.
    $permission['title'] = $this->t($permission['title'], $permission['title_args']);
    if (!empty($permission['description'])) {
      $permission['description'] = $this->t($permission['description'], $permission['description_args']);
    }
    if (!empty($permission['warning'])) {
      $permission['warning'] = $this->t($permission['warning'], $permission['warning_args']);
    }

    return $permission;
  }

  /**
   * Builds all permissions provided by .group.permissions.yml files.
   *
   * @return array[]
   *   An array of permissions as described in ::getPermissions().
   *
   * @see \Drupal\group\Access\PermissionHandlerInterface::getPermissions()
   */
  protected function buildPermissionsYaml() {
    $all_permissions = [];
    $all_callback_permissions = [];

    foreach ($this->getYamlDiscovery()->findAll() as $provider => $permissions) {
      // The top-level 'permissions_callback' is a list of methods in controller
      // syntax, see \Drupal\Core\Controller\ControllerResolver. These methods
      // should return an array of permissions in the same structure.
      if (isset($permissions['permission_callbacks'])) {
        foreach ($permissions['permission_callbacks'] as $permission_callback) {
          $callback = $this->controllerResolver->getControllerFromDefinition($permission_callback);
          if ($callback_permissions = call_user_func($callback)) {
            // Add any callback permissions to the array of permissions. In case
            // of any conflict, the YAML ones will take precedence.
            foreach ($callback_permissions as $name => $callback_permission) {
              if (!is_array($callback_permission)) {
                $callback_permission = ['title' => $callback_permission];
              }

              // Set the provider if none was specified.
              $callback_permission += ['provider' => $provider];

              // Set the section if none was specified.
              $callback_permission += ['section' => 'General'];

              $all_callback_permissions[$name] = $callback_permission;
            }
          }
        }

        unset($permissions['permission_callbacks']);
      }

      foreach ($permissions as $permission_name => $permission) {
        if (!is_array($permission)) {
          $permission = ['title' => $permission];
        }

        // Set the provider if none was specified.
        $permission += ['provider' => $provider];

        // Set the section if none was specified.
        $permission += ['section' => 'General'];

        $permissions[$permission_name] = $permission;
      }

      $all_permissions += $permissions;
    }

    // Combine all defined permissions and set the rest of the defaults.
    $full_permissions = $all_permissions + $all_callback_permissions;
    foreach ($full_permissions as $permission_name => $permission) {
      $full_permissions[$permission_name] = $this->completePermission($permission);
    }

    return $full_permissions;
  }

  /**
   * Sorts the given permissions by provider name first and then by title.
   *
   * @param array $permissions
   *   The permissions to be sorted.
   *
   * @return array[]
   *   An array of permissions as described in ::getPermissions().
   *
   * @see \Drupal\group\Access\PermissionHandlerInterface::getPermissions()
   */
  protected function sortPermissions(array $permissions = []) {
    $modules = $this->getModuleNames();

    // Sort all permissions by provider, section and title, in that order.
    uasort($permissions, function (array $permission_a, array $permission_b) use ($modules) {
      if ($permission_a['provider'] == $permission_b['provider']) {
        if ($permission_a['section'] == $permission_b['section']) {
          // All permissions should have gone through ::completePermission() so
          // the titles are \Drupal\Core\StringTranslation\TranslatableMarkup.
          $title_a = $permission_a['title']->__toString();
          $title_b = $permission_b['title']->__toString();
          return strip_tags($title_a) > strip_tags($title_b);
        }
        else {
          return $permission_a['section'] > $permission_b['section'];
        }
      }
      else {
        return $modules[$permission_a['provider']] > $modules[$permission_b['provider']];
      }
    });

    return $permissions;
  }

  /**
   * Returns all module names.
   *
   * @return string[]
   *   Returns the human readable names of all modules keyed by machine name.
   */
  protected function getModuleNames() {
    $modules = [];
    foreach (array_keys($this->moduleHandler->getModuleList()) as $module) {
      $modules[$module] = $this->moduleHandler->getName($module);
    }
    asort($modules);
    return $modules;
  }

}
