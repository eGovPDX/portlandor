<?php

namespace Drupal\portland\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\group\Entity\GroupContentType;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Adds the node's content type name to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "portland_content_type_name_field",
 *   label = @Translation("Content type name field"),
 *   description = @Translation("Adds the node's content type name to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddContentTypeName extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Content type name'),
        'description' => $this->t("The node's content type name"),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['portland_content_type_name_field'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // The entity being indexed now.
    $indexedEntity = $item->getOriginalObject()->getValue();

    if($indexedEntity->getEntityTypeId() == 'node') {
      $node = $indexedEntity;
      $type_name = \Drupal\node\Entity\NodeType::load($node->bundle())->label();

      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'portland_content_type_name_field');
      foreach ($fields as $field) {
        $field->addValue($type_name);
      }
    }
  }

}