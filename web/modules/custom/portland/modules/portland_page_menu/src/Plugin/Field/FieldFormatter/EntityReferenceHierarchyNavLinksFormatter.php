<?php

namespace Drupal\portland_page_menu\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[FieldFormatter(
  id: 'portland_entity_reference_hierarchy_nav_links',
  label: new TranslatableMarkup('Navigation links (back/next)'),
  description: new TranslatableMarkup('Display buttons for previous/next page depending on the user\'s current position in the menu.'),
  field_types: [
    'entity_reference_hierarchy'
  ]
)]
class EntityReferenceHierarchyNavLinksFormatter extends EntityReferenceFormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $current_nid = \Drupal::routeMatch()->getRawParameter('node') ?? \Drupal::routeMatch()->getRawParameter('node_preview');
    $source_entity = $items->getEntity();
    $referenced_entities = $this->getEntitiesToView($items, $langcode);
    $referenced_with_source = array_merge([$source_entity], $referenced_entities);
    $prev_url = null;
    $next_url = null;
    foreach ($referenced_with_source as $key => $node) {
      if ($current_nid === $node->id()) {
        if (array_key_exists($key - 1, $referenced_with_source)) {
          $prev_url = $referenced_with_source[$key - 1]->toUrl('canonical');
        }

        if (array_key_exists($key + 1, $referenced_with_source)) {
          $next_url = $referenced_with_source[$key + 1]->toUrl('canonical');
        }
        break;
      }
    }

    return
      [
        '#theme' => 'portland_entity_reference_hierarchy_nav_links',
        '#prev_url' => $prev_url,
        '#next_url' => $next_url,
      ];
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }
}
