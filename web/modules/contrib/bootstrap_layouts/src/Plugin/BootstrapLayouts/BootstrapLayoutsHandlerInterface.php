<?php

namespace Drupal\bootstrap_layouts\Plugin\BootstrapLayouts;

use Drupal\bootstrap_layouts\BootstrapLayout;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface BootstrapLayoutsHandlerInterface
 */
interface BootstrapLayoutsHandlerInterface extends ContainerAwareInterface, ContainerFactoryPluginInterface, DerivativeInspectionInterface, PluginInspectionInterface {

  /**
   * Retrieves the human readable label for the plugin.
   *
   * @return string
   *   The human readable label.
   */
  public function getLabel();

  /**
   * Loads a specific layout instance.
   *
   * @param string|int $id
   *   The identifier to load.
   *
   * @return \Drupal\bootstrap_layouts\BootstrapLayout
   *   The BootstrapLayout instance.
   */
  public function loadInstance($id);

  /**
   * Loads layout instances.
   *
   * @param string[]|int[] $ids
   *   Optional. An array of identifiers to load. If no identifiers are
   *   specified, then all available instances will be loaded.
   *
   * @return \Drupal\bootstrap_layouts\BootstrapLayout[]
   *   An associative array of BootstrapLayout instances, keyed by identifier.
   */
  public function loadInstances(array $ids = NULL);

  /**
   * Saves a specific layout instance.
   *
   * @param string|int $id
   *   The identifier to save.
   * @param \Drupal\bootstrap_layouts\BootstrapLayout $layout
   *   The layout instance info array.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function saveInstance($id, BootstrapLayout $layout);

  /**
   * Saves layout instances.
   *
   * @param \Drupal\bootstrap_layouts\BootstrapLayout[] $layouts
   *   An associative array of BootstrapLayout instances, keyed by identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  public function saveInstances(array $layouts = []);

}
