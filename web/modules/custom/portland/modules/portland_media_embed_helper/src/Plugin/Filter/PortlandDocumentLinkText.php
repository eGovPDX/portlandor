<?php

namespace Drupal\portland_media_embed_helper\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;

/**
 * @Filter(
 *   id = "portland_document_link_text",
 *   title = @Translation("Portland Document Link Text"),
 *   description = @Translation("When enabled, convert the data-alt-link-text attribute value into anchor text. Requries a custom embed dialog field to capture the link text. This filter must be processed after the Display Embedded Entities filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class PortlandDocumentLinkText extends FilterBase
{

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

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    // this section only gets used if data-alt-link-text attribute is present
    $elems = $xpath->query("//div[@data-alt-link-text]");
    foreach ($elems as $elem) {
      $alt_link_text = $elem->getAttribute("data-alt-link-text");
      if (empty($alt_link_text)) continue;
      $anchors = $elem->getElementsByTagName('a');
      foreach ($anchors as $anchor) {
        foreach ($anchor->childNodes as $child_node) {
          if ($child_node->nodeType === XML_TEXT_NODE) {
            $child_node->nodeValue = $alt_link_text;
          }
        }
      }
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
