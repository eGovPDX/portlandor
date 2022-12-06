<?php

namespace Drupal\portland\Plugin\search_api\processor;

use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exclude an entity from the search index.
 *
 * @SearchApiProcessor(
 *   id = "portland_exclude_entity_processor",
 *   label = @Translation("Exclude entity"),
 *   description = @Translation("Exclude an entity from the search index if the field ""Do not index"" is set."),
 *   stages = {
 *     "alter_items" = -50,
 *   }
 * )
 */

class ExcludeEntity extends ProcessorPluginBase {

  use PluginFormTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {

    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();

      // Remove Entities from indexed items.
      // Updated to match instructions at 
      // https://forelgroup.com/blog/drupal-8-search-api-exclude-items-from-index-based-on-field-value/
      // We need to be sure that the field actually exists
      // on the entity before fetching the value to avoid
      // InvalidArgumentException exceptions.
      if ($object !== null && $object->hasField('field_do_not_index')) {
        $value = $object->get('field_do_not_index')->getValue();
        if ($value[0]['value']) {
          unset($items[$item_id]);
          continue;
        }
      }
    }
  }

}
