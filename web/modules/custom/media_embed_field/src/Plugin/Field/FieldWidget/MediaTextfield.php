<?php

namespace Drupal\media_embed_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A widget to input media URLs.
 *
 * @FieldWidget(
 *   id = "media_embed_field_textfield",
 *   label = @Translation("Media Textfield"),
 *   field_types = {
 *     "media_embed_field"
 *   },
 * )
 */
class MediaTextfield extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => 60,
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => ['class' => ['js-text-full', 'text-full']],
      '#allowed_providers' => $this->getFieldSetting('allowed_providers'),
      '#theme' => 'input__media',
    ];
    return $element;
  }

}
