<?php

namespace Drupal\ds\Plugin\DsField\Node;

use Drupal\ds\Plugin\DsField\Title;

/**
 * Plugin that renders the title of a node.
 *
 * @DsField(
 *   id = "node_title",
 *   title = @Translation("Title"),
 *   entity_type = "node",
 *   provider = "node"
 * )
 */
class NodeTitle extends Title {

  /**
   * Constructs a Display Suite field plugin.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition) {
    // Set the title, used to construct the field label, based on the label
    // of the node type's title field.
    if (!empty($configuration['entity'])) {
      /* @var \Drupal\Core\Field\FieldDefinitionInterface $field */
      $field = $configuration['entity']->getFieldDefinition('title');
      $title = $field->getLabel();
      $configuration['field']['title'] = $title;
      $plugin_definition['title'] = $title;
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
