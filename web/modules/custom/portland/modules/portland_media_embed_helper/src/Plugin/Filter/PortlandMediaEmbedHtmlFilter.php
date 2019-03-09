<?php

namespace Drupal\portland_media_embed_helper\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;

/**
 * @Filter(
 *   id = "portland_media_embed_html_filter",
 *   title = @Translation("Portland Media Embed HTML Filter"),
 *   description = @Translation("Removes HTML elements that only contain non breaking spaces."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class PortlandMediaEmbedHtmlFilter extends FilterBase {

  /**
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode)
  {
    $result = new FilterProcessResult($text);

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $elems = $xpath->query( "//*[text()=\"\xC2\xA0\"]");
    foreach ($elems as $elem) {
      $elem->parentNode->removeChild($elem);
    }
    
    $result->setProcessedText(Html::serialize($dom))
      ->addAttachments([
        'library' => [
          'filter/caption',
        ],
      ]);

    return $result;
  }
}