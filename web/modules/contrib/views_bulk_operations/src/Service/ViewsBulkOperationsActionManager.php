<?php

namespace Drupal\views_bulk_operations\Service;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Defines Views Bulk Operations action manager.
 *
 * Extends the core Action Manager to allow VBO actions
 * define additional configuration.
 */
class ViewsBulkOperationsActionManager extends ActionManager {

  const ALTER_ACTIONS_EVENT = 'views_bulk_operations.action_definitions';

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Additional parameters passed to alter event.
   *
   * @var array
   */
  protected $alterParameters;

  /**
   * Service constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler to invoke the alter hook with.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cacheBackend,
    ModuleHandlerInterface $moduleHandler,
    EventDispatcherInterface $eventDispatcher
  ) {
    parent::__construct($namespaces, $cacheBackend, $moduleHandler);
    $this->eventDispatcher = $eventDispatcher;
    $this->setCacheBackend($cacheBackend, 'views_bulk_operations_action_info');
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();

    // Incompatible actions.
    $incompatible = [
      'node_delete_action',
      'user_cancel_user_action',
    ];

    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
      if (empty($definition) || in_array($definition['id'], $incompatible)) {
        unset($definitions[$plugin_id]);
      }
    }
    $this->alterDefinitions($definitions);
    foreach ($definitions as $plugin_id => $plugin_definition) {
      // If the plugin definition is an object, attempt to convert it to an
      // array, if that is not possible, skip further processing.
      if (is_object($plugin_definition) && !($plugin_definition = (array) $plugin_definition)) {
        continue;
      }
      // If this plugin was provided by a module that does not exist, remove the
      // plugin definition.
      if (isset($plugin_definition['provider']) && !in_array($plugin_definition['provider'], ['core', 'component']) && !$this->providerExists($plugin_definition['provider'])) {
        unset($definitions[$plugin_id]);
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $parameters
   *   Parameters of the method. Passed to alter event.
   */
  public function getDefinitions(array $parameters = []) {
    if (empty($parameters['nocache'])) {
      $definitions = $this->getCachedDefinitions();
    }
    if (!isset($definitions)) {
      $this->alterParameters = $parameters;
      $definitions = $this->findDefinitions($parameters);

      $this->setCachedDefinitions($definitions);
    }

    return $definitions;
  }

  /**
   * Gets a specific plugin definition.
   *
   * @param string $plugin_id
   *   A plugin id.
   * @param bool $exception_on_invalid
   *   (optional) If TRUE, an invalid plugin ID will throw an exception.
   * @param array $parameters
   *   Parameters of the method. Passed to alter event.
   *
   * @return mixed
   *   A plugin definition, or NULL if the plugin ID is invalid and
   *   $exception_on_invalid is FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if $plugin_id is invalid and $exception_on_invalid is TRUE.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE, array $parameters = []) {
    // Loading all definitions here will not hurt much, as they're cached,
    // and we need the option to alter a definition.
    $definitions = $this->getDefinitions($parameters);
    if (isset($definitions[$plugin_id])) {
      return $definitions[$plugin_id];
    }
    elseif (!$exception_on_invalid) {
      return NULL;
    }

    throw new PluginNotFoundException($plugin_id, sprintf('The "%s" plugin does not exist.', $plugin_id));
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    // Only arrays can be operated on.
    if (!is_array($definition)) {
      return;
    }

    if (!empty($this->defaults) && is_array($this->defaults)) {
      $definition = NestedArray::mergeDeep($this->defaults, $definition);
    }

    // Merge in defaults.
    $definition += [
      'confirm' => FALSE,
    ];

    // Add default confirmation form if confirm set to TRUE
    // and not explicitly set.
    if ($definition['confirm'] && empty($definition['confirm_form_route_name'])) {
      $definition['confirm_form_route_name'] = 'views_bulk_operations.confirm';
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Let other modules change definitions.
    // Main purpose: Action permissions bridge.
    $event = new Event();
    $event->alterParameters = $this->alterParameters;
    $event->definitions = &$definitions;
    $this->eventDispatcher->dispatch(static::ALTER_ACTIONS_EVENT, $event);
  }

}
