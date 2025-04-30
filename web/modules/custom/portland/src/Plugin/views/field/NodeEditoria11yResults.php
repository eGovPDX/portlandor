<?php

namespace Drupal\portland\Plugin\views\field;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display the total count of editoria11y issues per node.
 *
 * @ViewsField("node_editoria11y_results")
 */
class NodeEditoria11yResults extends FieldPluginBase implements ContainerFactoryPluginInterface {
  protected ViewsHandlerManager $joinHandler;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinHandler = $join_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $configuration = [
      'table' => 'editoria11y_results',
      'field' => 'entity_id',
      'left_table' => $this->tableAlias,
      'left_field' => 'nid',
      'type' => 'LEFT',
      'extra' => [
        [
          'field' => 'page_language',
          'value' => 'en',
        ],
        [
          'field' => 'route_name',
          'value' => "entity.{$this->configuration['entity_type']}.canonical",
        ],
      ],
    ];
    /** @var \Drupal\views\Plugin\views\join\Standard $join */
    $join = $this->joinHandler->createInstance('standard', $configuration);
    $join_table_alias = $this->query->addTable('editoria11y_results', $this->relationship, $join);

    $this->field_alias = $this->query->addField($join_table_alias, 'page_result_count', NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $rows) {
    return $rows->{$this->field_alias} ?? 0;
  }
}
