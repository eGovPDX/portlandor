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
 * Adds the node's group title to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "portland_group_title_field",
 *   label = @Translation("Group title field"),
 *   description = @Translation("Adds the node's group title to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AddGroupTitle extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Group title'),
        'description' => $this->t("The node's group title"),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['portland_group_title_field'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // General approach
    // 1. Search for this NID in the group content table.
    // 2. If found a group content item, get the gid and group title.
    // 3. Set the index value.

    // The entity being indexed now.
    $indexedEntity = $item->getOriginalObject()->getValue();

    if($indexedEntity->getEntityTypeId() == 'group') {
      // Get group title
      $title = $indexedEntity->label->value;

      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'portland_group_title_field');
      foreach ($fields as $field) {
        $field->addValue($title);
      }
    }
    else if($indexedEntity->getEntityTypeId() == 'node') {
      $node = $indexedEntity;
      $plugin_id = 'group_node:' . $node->bundle();

      // Only act if there are group content types for this node type.
      $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);
      if (empty($group_content_types)) {
        return;
      }

      // Load all the group content for this node.
      $group_contents = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $node->id(),
      ]);
      if(empty($group_contents)) return; // ignore nodes without a parent group

      $group_content = reset($group_contents); // get the first value
      $title = $group_content->gid->entity->label->value; // get the gid

      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'portland_group_title_field');
      foreach ($fields as $field) {
        $field->addValue($title);
      }
    }
  }

}