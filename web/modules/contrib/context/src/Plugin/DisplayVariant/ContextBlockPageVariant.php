<?php

namespace Drupal\context\Plugin\DisplayVariant;

use Drupal\Component\Utility\NestedArray;
use Drupal\context\ContextManager;
use Drupal\Core\Display\VariantBase;
use Drupal\Core\Display\PageVariantInterface;
use Drupal\Core\Display\VariantManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a page display variant that decorates the main content with blocks.
 *
 * @see \Drupal\Core\Block\MainContentBlockPluginInterface
 * @see \Drupal\Core\Block\MessagesBlockPluginInterface
 *
 * @PageDisplayVariant(
 *   id = "context_block_page",
 *   admin_label = @Translation("Page with blocks")
 * )
 */
class ContextBlockPageVariant extends VariantBase implements PageVariantInterface, ContainerFactoryPluginInterface {

  /**
   * @var ContextManager
   */
  protected $contextManager;

  /**
   * The render array representing the main page content.
   *
   * @var array
   */
  protected $mainContent = [];

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

  /**
   * Constructs a new ContextBlockPageVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   *
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @param ContextManager $contextManager
   *   The context module manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextManager $contextManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contextManager = $contextManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setMainContent(array $main_content) {
    $this->mainContent = $main_content;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#cache' => [
        'tags' => ['context_block_page', $this->getPluginId()],
      ],
    ];

    // Place main content block, it will be removed by the reactions if a main
    // content block has been manually placed.
    $build['content']['system_main'] = $this->mainContent;

    // Execute each block reaction and let them modify the page build.
    foreach ($this->contextManager->getActiveReactions('blocks') as $reaction) {
      $build = $reaction->execute($build, $this->title, $this->mainContent);
    }

    // Execute each block reaction and check if default block should be included in page build.
    foreach ($this->contextManager->getActiveReactions('blocks') as $reaction) {
      if ($reaction->includeDefaultBlocks()) {
        $build = NestedArray::mergeDeep($this->getBuildFromBlockLayout(), $build);
        return $build;
      }
    }
    return $build;
  }

  /**
   * Get build from Block layout.
   */
  private function getBuildFromBlockLayout() {
    $plugin_manager = \Drupal::service('plugin.manager.display_variant');
    $display_variant = $plugin_manager->createInstance('block_page', $plugin_manager->getDefinition('block_page'));
    $display_variant->setTitle($this->title);

    return $display_variant->build();
  }

}
