<?php

namespace Drupal\bootstrap_layouts\Plugin\BootstrapLayouts;

use Drupal\bootstrap_layouts\BootstrapLayout;
use Drupal\bootstrap_layouts\BootstrapLayoutsManager;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

interface BootstrapLayoutsUpdateInterface extends ContainerAwareInterface, ContainerFactoryPluginInterface, DerivativeInspectionInterface, PluginInspectionInterface {

  /**
   * Retrieves the path to plugin provider.
   *
   * @return string
   *   Path to the plugin provider.
   */
  public function getPath();

  /**
   * Provide an update.
   *
   * @param \Drupal\bootstrap_layouts\BootstrapLayoutsManager $manager
   *   The BootstrapLayoutsManager instance.
   * @param array $data
   *   Any static YAML data found for the update.
   * @param bool $display_messages
   *   Flag determining whether a message will be displayed indicating whether
   *   the layout was processed successfully or not.

   */
  public function update(BootstrapLayoutsManager $manager, array $data = [], $display_messages = TRUE);

  /**
   * Provide an update for an existing layout.
   *
   * Note: this process any existing layout and is not specific to just
   * "Bootstrap Layouts" based layouts. If implementing this update, you should
   * check the $layout->getId() before performing any tasks.
   *
   * @param \Drupal\bootstrap_layouts\BootstrapLayout $layout
   *   The existing BootstrapLayout instance that is being processed.
   * @param array $data
   *   Any static YAML data found for the update.
   * @param bool $display_messages
   *   Flag determining whether a message will be displayed indicating whether
   *   the layout was processed successfully or not.
   *
   * @see \Drupal\bootstrap_layouts\BootstrapLayoutsManager::update()
   */
  public function processExistingLayout(BootstrapLayout $layout, array $data = [], $display_messages = TRUE);

}
