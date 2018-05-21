<?php

namespace Drupal\bootstrap_layouts\Plugin\BootstrapLayouts;

use Drupal\bootstrap_layouts\BootstrapLayout;
use Drupal\bootstrap_layouts\BootstrapLayoutsManager;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BootstrapLayoutsUpdateBase extends PluginBase implements BootstrapLayoutsUpdateInterface {

  use ContainerAwareTrait;

  /**
   * The path to the provider.
   *
   * @var string
   */
  protected $path;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!isset($container)) {
      $container = \Drupal::getContainer();
    }
    $this->setContainer($container);

    // Retrieve the path to provider.
    $this->path = drupal_get_path('module', $this->pluginDefinition['provider']) ?: drupal_get_path('theme', $this->pluginDefinition['provider']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container);
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function update(BootstrapLayoutsManager $manager, array $data = [], $display_messages = TRUE) {
  }

  /**
   * {@inheritdoc}
   */
  public function processExistingLayout(BootstrapLayout $layout, array $data = [], $display_messages = TRUE) {
  }

}
