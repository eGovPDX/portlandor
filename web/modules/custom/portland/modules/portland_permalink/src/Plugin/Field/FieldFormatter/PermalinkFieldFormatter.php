<?php

namespace Drupal\portland_permalink\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'portland_permalink_download_link' formatter.
 *
 * @FieldFormatter(
 *   id = "portland_permalink_download_link",
 *   label = @Translation("Portland Permalink"),
 *   field_types = {
 *     "file",
 *     "image"
 *   }
 * )
 */
class PermalinkFieldFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $parent = $items->getParent()->getValue()->id();

    foreach ($items as $delta => $item) {

      $route_parameters = ['media' => $parent];
      if ($delta > 0) {
        $route_parameters['query']['delta'] = $delta;
      }

      // @todo: replace with DI when this issue is fixed: https://www.drupal.org/node/2053415
      /** @var \Drupal\file\FileInterface $file */
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($item->target_id);
      $filename = $file->getFilename();
      $mime_type = $file->getMimeType();

      $options = [
        'attributes' => [
          'type' => "$mime_type; length={$file->getSize()}",
          'title' => $filename,
          // Classes to add to the file field for icons.
          'class' => [
            'file',
            // Add a specific class for each and every mime type.
            'file--mime-' . strtr($mime_type, ['/' => '-', '.' => '-']),
            // Add a more general class for groups of well known MIME types.
            'file--' . file_icon_class($mime_type),
          ],
        ],
      ];

      //$url = Url::fromRoute('portland_permalink.download', $route_parameters, $options);
      //$url = $this->buildUrl($item);
      $url = Url::fromUri('https://portlandor.lndo.site/media/5/download');


      $elements[$delta] = [
        '#type' => 'link',
        '#url' => $url,
        '#title' => $filename,
        '#options' => $options,
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getFieldStorageDefinition()->getTargetEntityTypeId() == 'media');
  }

}
