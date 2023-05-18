<?php

namespace Drupal\portland\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to process data-aspect-ratio.
 *
 * @Filter(
 *   id = "filter_aspect_ratio",
 *   title = @Translation("Set aspect ratio"),
 *   description = @Translation("Uses a <code>data-aspect-ratio</code> attribute on <code>&lt;drupal-entity&gt;</code> tags to set aspect ratio."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterAspectRatio extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-entity-uuid') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-entity-uuid]') as $node) {
        // Check if it's an iframe
        $uuid = $node->getAttribute('data-entity-uuid');
        if (empty($uuid)) continue;

        $entity_loaded_by_uuid = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['uuid' => $uuid]);
        if (count($entity_loaded_by_uuid) == 0) continue;
        $entity_loaded_by_uuid = reset($entity_loaded_by_uuid);
        if ($entity_loaded_by_uuid->bundle() != 'iframe_embed') continue;

        // Read the data-aspect-ratio attribute's value, then delete it.
        // HACK ALERT: Entity Embed dialog no longer allows data-aspect-ratio attribute, so we've hijacked the
        // title attribute and are using it to store the value now.
        $aspect_ratio = $node->getAttribute('title');
        $node->removeAttribute('title');

        // Default ratio is 16x9
        if (empty($aspect_ratio)) {
          $node->setAttribute('style', 'aspect-ratio: 16/9');
        }
        // Only allow pre-defined ratios
        else if (in_array($aspect_ratio, ['16/9', '4/3', '1/1', '9/16'])) {
          $node->setAttribute('style', "aspect-ratio: $aspect_ratio");
        }
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('<p>You can set aspect ratio to 16x9, 4x3, or 1x1.</p>');
  }
}
