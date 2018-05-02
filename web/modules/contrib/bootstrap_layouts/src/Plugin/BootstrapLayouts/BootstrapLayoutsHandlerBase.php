<?php

namespace Drupal\bootstrap_layouts\Plugin\BootstrapLayouts;

use Drupal\bootstrap_layouts\BootstrapLayout;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BootstrapLayoutsHandlerBase
 */
abstract class BootstrapLayoutsHandlerBase extends PluginBase implements BootstrapLayoutsHandlerInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContainerInterface $container = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!isset($container)) {
      $container = \Drupal::getContainer();
    }
    $this->setContainer($container);
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
  public function getLabel() {
    return isset($this->pluginDefinition['label']) ? $this->pluginDefinition['label'] : $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function loadInstance($entity_id) {
    $layouts = $this->loadInstances([$entity_id]);
    return reset($layouts);
  }

  /**
   * {@inheritdoc}
   */
  public function saveInstance($entity_id, BootstrapLayout $layout) {
    $this->saveInstances([$entity_id => $layout]);
  }

}
