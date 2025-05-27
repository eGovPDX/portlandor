<?php

namespace Drupal\portland_page_menu\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_reference_hierarchy\EntityReferenceHierarchyFieldItemList;

/** Borrowed from entity_reference_hierarchy EntityReferenceHierarchyFormatterTrait and Drupal core EntityReferenceLabelFormatter. */
#[FieldFormatter(
  id: 'portland_entity_reference_hierarchy_page_menu',
  label: new TranslatableMarkup('Page menu (with hierarchy)'),
  description: new TranslatableMarkup('Display the short titles of referenced entities in a hierarchical page menu.'),
  field_types: [
    'entity_reference_hierarchy'
  ]
)]
class EntityReferenceHierarchyPageMenuFormatter extends EntityReferenceFormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $current_node = \Drupal::routeMatch()->getParameter('node') ?? \Drupal::routeMatch()->getParameter('node_preview');
    $current_nid = $current_node ? $current_node->id() : -1;
    /** @var EntityReferenceHierarchyFieldItemList $items */
    $elements = [];
    $output_as_link = true;
    $referenced_entities = $this->getEntitiesToView($items, $langcode);

    foreach ($referenced_entities as $delta => $entity) {
      $label = $entity->field_menu_link_text->value ?: $entity->label();
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$entity->isNew()) {
        try {
          $uri = $entity->toUrl();
        }
        catch (UndefinedLinkTemplateException $e) {
          // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
          // and it means that the entity type doesn't have a link template nor
          // a valid "uri_callback", so don't bother trying to output a link for
          // the rest of the referenced entities.
          $output_as_link = false;
        }
      }

      if ($output_as_link && isset($uri) && !$entity->isNew()) {
        $is_active = $current_nid == $entity->id();
        $published = $entity->isPublished();
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => [
            '#markup' => Html::escape($label) . ($published ? '' : ' <span class="badge text-bg-danger">Unpublished</strong>')
          ],
          '#url' => $uri,
          '#options' => $uri->getOptions(),
          '#attributes' => [
            'aria-current' => $is_active ? 'page' : NULL,
            'class' => ['nav-link', $is_active ? 'active' : ''],
          ],
        ];

        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += ['attributes' => []];
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = ['#plain_text' => $label];
      }
      $elements[$delta]['#entity'] = $entity;
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    // Get outline.
    $outline = $items->getFieldHierarchyOutline();
    if (empty($outline)) {
      return [];
    }

    // Build list items according to the outline.
    $list = $this->getNestedListItemElements($elements, $outline, $referenced_entities, $current_nid);
    // Add the source entity to the top of the list,
    $source_entity = $items->getEntity();
    if ($source_entity) {
      $is_active = $current_nid == $source_entity->id();
      array_unshift($list,
        [
          '#type' => 'html_tag',
          '#tag' => 'li',
          '#attributes' => [
            'class' => 'nav-item' . ($is_active ? ' active' : ''),
          ],
          0 => [
            [
              '#type' => 'link',
              '#title' => t('Overview'),
              '#url' => $source_entity->toUrl('canonical'),
              '#attributes' => [
                'aria-current' => $is_active ? 'page' : NULL,
                'class' => array_merge(['nav-link'], $is_active ? ['active'] : []),
              ],
            ],
          ],
        ]
      );
    }

    return [ $list ];
  }

  /**
   * Prepares items to be rendered inside a <ul>.
   */
  public function getNestedListItemElements(array $elements, array $delta_outline, array $referenced_entities, int $current_nid): array {
    $result = [
      '#type' => 'html_tag',
      '#tag' => 'ul',
      '#attributes' => [
        'class' => 'nav nav-page-menu flex-column',
      ],
      '#cache' => [
        'contexts' => [
          'url.path'
        ],
      ],
    ];

    foreach ($delta_outline as $delta => $branch) {
      if (!array_key_exists($delta, $referenced_entities)) continue;

      $is_active = $current_nid == $referenced_entities[$delta]->id() || $this->hasActiveChildren($branch['children'], $referenced_entities, $current_nid);
      if ($is_active) {
        $elements[$delta]['#attributes']['class'][] = 'active';
      }
      $item = [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#attributes' => [
          'class' => 'nav-item' . ($is_active ? ' active' : ''),
        ],
        0 => $elements[$delta],
      ];

      // Add children if available and active.
      if (!empty($branch['children']) && $is_active) {
        $item[] = $this->getNestedListItemElements($elements, $branch['children'], $referenced_entities, $current_nid);
      }

      $result[] = $item;
    }

    return $result;
  }

  public function hasActiveChildren(array $delta_outline, array $referenced_entities, int $current_nid) {
    $has_active_children = false;
    foreach ($delta_outline as $delta => $branch) {
      if (array_key_exists($delta, $referenced_entities) && $referenced_entities[$delta]->id() == $current_nid) {
        $has_active_children = true;
        break;
      }

      // Check children if available.
      if (!empty($branch['children'])) {
        $has_active_children = $this->hasActiveChildren($branch['children'], $referenced_entities, $current_nid);
        if ($has_active_children) {
          break;
        }
      }
    }

    return $has_active_children;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }
}
