<?php

/**
 * @file
 * Definition of Drupal\portland\Plugin\Field\FieldFormatter\PluralLabelFormatter.
 */

namespace Drupal\portland\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\String\Inflector\EnglishInflector;

/**
 * Plugin implementation of the 'leaflet_default' formatter.
 *
 * @FieldFormatter(
 *   id = "portland_plural_label",
 *   label = @Translation("Label Pluralized"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class PluralLabelFormatter extends EntityReferenceFormatterBase {

  private EnglishInflector $inflector;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct(...func_get_args());  
    
    $this->inflector = new EnglishInflector();
  }


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();

      $elements[$delta] = ['#plain_text' => $this->inflector->pluralize($label)[0]];
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }

}
