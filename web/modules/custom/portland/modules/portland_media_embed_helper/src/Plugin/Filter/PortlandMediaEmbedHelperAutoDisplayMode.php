<?php

namespace Drupal\portland_media_embed_helper\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;

/**
 * @Filter(
 *   id = "portland_media_embed_helper_auto_mode_filter",
 *   title = @Translation("Portland Media Embed Helper Auto Display Mode Filter"),
 *   description = @Translation("Sets the display mode for embedded images automatically based on whether they are right-aligned or full width."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class PortlandMediaEmbedHelperAutoDisplayMode extends FilterBase {

  const DISPLAY_MODE_RIGHT = "embedded_50";
  const DISPLAY_MODE_FULL = "embedded_100";

  /**
   * Processes text in the following ways: 
   * - Checks the data-align attribute to determine whether it's Full or Right alignment, and changes the data-entity-embed-display
   *   attribute to automatically use the Embedded100 (full) or Embedded50 (right) entity display mode.
   *
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode)
  {
    $result = new FilterProcessResult($text);

    // spin through all drupal-entity elements with data embed button values
    // for each button...
    //   if data-align is set and set to "right," and type is image, set display mode to Embedded50
    //   if data-align is set and type is image, set display mode to Embedded100
    if (stristr($text, 'data-embed-button') !== false) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-embed-button]') as $node) {
        $embed_button = Html::escape($node->getAttribute('data-embed-button'));
        $alignment = Html::escape($node->getAttribute('data-align'));

        if ($embed_button == "image_browser") {
          if (!is_null($alignment) && $alignment == "right") {
            $display = DISPLAY_MODE_RIGHT;
          } else {
            $display = DISPLAY_MODE_FULL;
          }
          // change value of data-entity-embed-display="view_mode:media.embedded"
          $node->setAttribute("data-entity-embed-display", "view_mode:media." . $display);
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