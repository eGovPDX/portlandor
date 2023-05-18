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
 *   description = @Translation("When enabled, convert the <code>data-alt-link-text</code> attribute value into anchor text. Requries a custom embed dialog field to capture the link text. This filter must be processed after the Display Embedded Entities filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class PortlandDocumentLinkText extends FilterBase
{

  /**
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-entity-uuid') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-entity-uuid]') as $node) {
        // Check if it's a document
        $uuid = $node->getAttribute('data-entity-uuid');
        if (empty($uuid)) continue;

        $entity_loaded_by_uuid = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['uuid' => $uuid]);
        if (count($entity_loaded_by_uuid) == 0) continue;
        $entity_loaded_by_uuid = reset($entity_loaded_by_uuid);
        if ($entity_loaded_by_uuid->bundle() != 'document') continue;

        // Read the data-alt-link-text attribute's value, then delete it.
        // HACK ALERT: Entity Embed dialog no longer allows data-alt-link-text attribute, so we've hijacked the
        // title attribute and are using it to store the value now.
        $alt_link_text = $node->getAttribute('title');
        $node->removeAttribute('title');

        if (empty($alt_link_text)) continue;
        $anchors = $node->getElementsByTagName('a');
        foreach ($anchors as $anchor) {
          foreach ($anchor->childNodes as $child_node) {
            if (!empty($anchor->getAttribute('lang'))) continue; // Only set Link Text for English version
            if ($child_node->nodeType === XML_TEXT_NODE) {
              $child_node->nodeValue = trim($alt_link_text);
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
    }
    
    return $result;
  }
}
