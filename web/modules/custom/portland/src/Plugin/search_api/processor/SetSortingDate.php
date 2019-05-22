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
 * Set the Date that Solr uses to rank the freshness of content.
 * Published date For News, Start date for Event, Published date for Page
 * Changed date for other types
 *
 * @SearchApiProcessor(
 *   id = "portland_sorting_date_field",
 *   label = @Translation("Sorting date field"),
 *   description = @Translation("Set the Date that Solr uses to rank the freshness of content."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SetSortingDate extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Sorting date'),
        'description' => $this->t("The Date that Solr uses to rank the freshness of content."),
        'type' => 'date',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['portland_sorting_date_field'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    /*
    * Published date For News, Start date for Event, Published date for Page
    * Changed date for other types
    */

    // The entity being indexed now.
    $indexedEntity = $item->getOriginalObject()->getValue();

    if($indexedEntity->getEntityTypeId() == 'node') {
      $node = $indexedEntity;
      $sorting_date = null;
      if($node->bundle() === 'news') {
        $sorting_date = $node->field_published_on->value;
      }
      else if($node->bundle() === 'event') {
        $sorting_date = $node->field_start_date->value;
      }
      else {
        $sorting_date = $node->changed->value;
      }

      $fields = $item->getFields(FALSE);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($fields, NULL, 'portland_sorting_date_field');
      foreach ($fields as $field) {
        $field->addValue($sorting_date);
      }
    }
  }

}