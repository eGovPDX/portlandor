<?php

namespace Drupal\shs\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity reference taxonomy term SHS' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_shs",
 *   label = @Translation("Simple hierarchical select"),
 *   description = @Translation("Display reference to taxonomy term with SHS."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceShsFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    if (!isset($elements['link'])) {
      return $elements;
    }
    // Override title of setting.
    $elements['link']['#title'] = t('Link item labels to the referenced entities');

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('link') ? t('Link item labels to the referenced entities') : t('Do not create links');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_as_link = $this->getSetting('link');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();
      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$entity->isNew()) {
        try {
          $uri = $entity->urlInfo();
        }
        catch (UndefinedLinkTemplateException $e) {
          // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
          // and it means that the entity type doesn't have a link template nor
          // a valid "uri_callback", so don't bother trying to output a link for
          // the rest of the referenced entities.
          $output_as_link = FALSE;
        }
      }

      try {
        $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
        $parents = $storage->loadAllParents($entity->id());
      }
      catch (Exception $ex) {
        $parents = [];
      }

      $list_items = [];

      // Create hierarchy from parent items.
      foreach (array_reverse($parents) as $entity_parent) {
        if ($entity_parent->hasTranslation($langcode)) {
          $entity_parent = $entity_parent->getTranslation($langcode);
        }
        if ($output_as_link) {
          $uri_parent = $entity_parent->urlInfo();
          $list_items[] = [
            '#type' => 'link',
            '#title' => $entity_parent->label(),
            '#url' => $uri_parent,
            '#options' => $uri_parent->getOptions(),
          ];
        }
        else {
          $list_items[] = ['#plain_text' => $entity_parent->label()];
        }
      }

      $elements[$delta] = [
        '#theme' => 'item_list',
        '#items' => $list_items,
        '#attributes' => [
          'class' => ['shs'],
        ],
        '#attached' => [
          'library' => ['shs/shs.formatter'],
        ],
      ];

      if (!empty($items[$delta]->_attributes)) {
        if (empty($elements[$delta]['#options'])) {
          $elements[$delta]['#options'] = [];
        }
        $elements[$delta]['#options'] += ['attributes' => []];
        $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and shouldn't be rendered in the field template.
        unset($items[$delta]->_attributes);
      }
      if ($output_as_link) {
        $elements[$delta]['#attributes']['class'][] = 'shs-linked';
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is available for taxonomy terms only.
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'taxonomy_term';
  }

}
