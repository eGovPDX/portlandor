<?php

namespace Drupal\portland\Plugin\search_api\processor;

use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Exclude an entity from the search index.
 *
 * @SearchApiProcessor(
 *   id = "portland_exclude_entity_processor",
 *   label = @Translation("Exclude entity"),
 *   description = @Translation("Exclude an entity from the search index if the field ""Do not index"" is set."),
 *   stages = {
 *     "alter_items" = 0,
 *   }
 * )
 */
class ExcludeEntity extends ProcessorPluginBase {
  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    foreach ($items as $item_id => $item) {
      $entity = $item->getOriginalObject()->getEntity();
      // Check the entity's "exclude from search" value
      if($entity !== null && 
        $entity->hasField('field_do_not_index') && 
        $entity->field_do_not_index->value) {
        unset($items[$item_id]);
      }
    }
  }
}
