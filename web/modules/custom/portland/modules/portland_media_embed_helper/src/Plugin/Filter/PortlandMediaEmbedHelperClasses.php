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
 *   description = @Translation("Adds classes to entity embed containers and pre-selects image display mode based on alignment selections. This filter must be executed before Align, Caption, or Display Embedded Entities."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
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
  public function process($text, $langcode)
  {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-embed-button') !== false) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-embed-button]') as $node) {
        // Read the data-caption attribute's value; this is used to determine media type
        $embed_button = Html::escape($node->getAttribute('data-embed-button'));

        // alignment is used to determine image display mode
        $alignment = Html::escape($node->getAttribute('data-align'));
        // if empty, default is responsive-right/embedded_100
        if (!$alignment) {
          $alignment = "responsive-full";
        }

        // the embed tag may be enclosed in a <figure> provided by the filter-caption template.
        // that is where we want to put the media type class.
        $node = ($node->parentNode->tagName === 'figure') ? $node->parentNode : $node;

        // set class based on embed button id; this is not ideal, since it relies on configuration
        $media_class = "";
        switch ($embed_button) {
          case "document_browser":
            $media_class = "embed-document";
            break;

          case "image_browser":
            $media_class = "embed-image";
            // for images, set the display mode based on alignment
            $display = "embedded";
            if (!is_null($alignment) && $alignment == "responsive-right") {
              $display = "embedded_50";
              $media_class .= " responsive-right";
            } else if (!is_null($alignment) && $alignment == "responsive-full") {
              $display = "embedded_100";
              $media_class .= " responsive-full";
            } else {
              $media_class .= " embedded-right";
            }
            // change value of data-entity-embed-display="view_mode:media.embedded"
            $node->setAttribute("data-entity-embed-display", "view_mode:media." . $display);
            break;

          case "audio_video_browser":
          case "map_browser":
            $media_class = "embed-video";
            if (!is_null($alignment) && $alignment == "responsive-right") {
              $media_class .= " responsive-right";
            }
            break;
        }

        // if there are already classes on the parent element, update them with media type class.
        $classes = $node->getAttribute('class');
        $node->removeAttribute('class');
        $classes = (strlen($classes) > 0) ? explode(' ', $classes) : [];
        $classes[] = $media_class;
        $node->setAttribute('class', implode(' ', $classes));
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