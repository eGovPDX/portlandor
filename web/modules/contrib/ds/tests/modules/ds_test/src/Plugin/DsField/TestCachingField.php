<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Test caching field plugin.
 *
 * @DsField(
 *   id = "test_caching_field",
 *   title = @Translation("Test Caching field"),
 *   entity_type = "node"
 * )
 */
class TestCachingField extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $cache = CacheableMetadata::createFromRenderArray($build);
    $cache
      ->addCacheTags(['ds_my_custom_tags'])
      ->applyTo($build);

    if (\Drupal::state()->get('ds_test_show_field', FALSE)) {
      $build['#markup'] = 'DsField Shown';
      return $build;
    }

    // Returning the cache object when we only have cache data allows our field
    // to appear when the node changes status.
    return $cache;
  }

}
