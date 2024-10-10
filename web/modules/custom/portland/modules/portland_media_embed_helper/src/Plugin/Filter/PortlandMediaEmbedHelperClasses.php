<?php

namespace Drupal\portland_media_embed_helper\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;

/**
 * @Filter(
 *   id = "portland_media_embed_helper_filter",
 *   title = @Translation("Portland Media Embed Helper Filter"),
 *   description = @Translation("Adds classes to entity embed containers and sets image view mode based on alignment selection. This filter must be executed before Caption or Display Embedded Entities."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class PortlandMediaEmbedHelperClasses extends FilterBase {

  /**
   * Processes text in the following ways:
   * - Adds classes to entity embed containers that correspond to the media type, so they can be styled
   * - Pre-sets the display mode for images based on the alignment option selected in the embed dialog
   *
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (str_contains($text, 'data-embed-button')) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-embed-button]') as $node) {
        // Read the data-caption attribute's value; this is used to determine media type
        $embed_button = Html::escape($node->getAttribute('data-embed-button'));

        // alignment is used to determine image display mode
        $alignment = Html::escape($node->getAttribute('data-align')) ?: '';

        // the embed tag may be enclosed in a <figure> provided by the filter-caption template.
        // that is where we want to put the media type class.
        $node = ($node->parentNode->tagName === 'figure') ? $node->parentNode : $node;

        if ($embed_button === 'image_browser') {
          $alignment_to_view_mode_map = [
            // Full width
            'responsive-full' => 'embedded_100',
            // Narrow
            'narrow' => 'embedded',
            // 50% fill
            'responsive-right' => 'embedded_50',
            // 50% fit
            'right' => 'embedded',
            // None
            '' => 'embedded',
          ];

          // change to corresponding view mode based on alingment
          $node->setAttribute('data-entity-embed-display', 'view_mode:media.' . $alignment_to_view_mode_map[$alignment] ?? 'embedded');
        }

        if (!empty($alignment)) {
          $existing_classes = $node->getAttribute('class');
          $classes = empty($existing_classes) ? [] : explode(' ', $existing_classes);
          $classes[] = 'align-' . $alignment;
          $node->setAttribute('class', implode(' ', $classes));
        }
      }

      $result->setProcessedText(Html::serialize($dom))
        ->addAttachments([
          'library' => [
            'filter/caption',
          ],
        ]);
    }

    return $result;
  }
}
