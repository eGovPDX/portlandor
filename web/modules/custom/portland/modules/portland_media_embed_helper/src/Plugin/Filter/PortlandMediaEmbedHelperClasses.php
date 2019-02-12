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
 *   title = @Translation("Portland Media Embed Helper Classes Filter"),
 *   description = @Translation("Adds classes to entity embed containers that correspond to the selected media browser"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class PortlandMediaEmbedHelperClasses extends FilterBase {

  /**
   * Processes text in the following ways: 
   * - 
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
        // Read the data-caption attribute's value
        $embed_button = Html::escape($node->getAttribute('data-embed-button'));
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
            break;

          case "audio_video_browser":
            $media_class = "embed-video";
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