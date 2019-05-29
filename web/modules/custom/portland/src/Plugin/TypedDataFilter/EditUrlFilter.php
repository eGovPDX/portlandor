<?php

namespace Drupal\portland\Plugin\TypedDataFilter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\typed_data\DataFilterBase;

/**
 * A data filter that provides the URL of an entity's edit page.
 *
 * @DataFilter(
 *   id = "edit_url",
 *   label = @Translation("Provides the URL of an entity's edit page."),
 * )
 */
class EditUrlFilter extends DataFilterBase {

  /**
   * {@inheritdoc}
   */
  public function filter(DataDefinitionInterface $definition, $value, array $arguments, BubbleableMetadata $bubbleable_metadata = NULL) {
    assert($value instanceof EntityInterface);
    // @todo url() is deprecated, but toUrl() does not work for file entities,
    // thus remove url() once toUrl() works for file entities also.
    // return $value->url('canonical', ['absolute' => TRUE]);

    global $base_url;
    $base_url_parts = parse_url($base_url);
    $host = $base_url_parts['host'];
    $nid = $value->id();
    return "https://$host/node/$nid/edit";
  }

  /**
   * {@inheritdoc}
   */
  public function canFilter(DataDefinitionInterface $definition) {
    return $definition instanceof EntityDataDefinitionInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function filtersTo(DataDefinitionInterface $definition, array $arguments) {
    return DataDefinition::create('uri');
  }

}
