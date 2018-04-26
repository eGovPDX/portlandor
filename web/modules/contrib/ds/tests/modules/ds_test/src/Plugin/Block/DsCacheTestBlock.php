<?php

namespace Drupal\ds_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Defines a Display suite cache test block.
 *
 * @Block(
 *   id = "ds_cache_test_block",
 *   admin_label = @Translation("Display Suite Cache Test Block"),
 *   category = @Translation("ds")
 * )
 */
class DsCacheTestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = \Drupal::service('request_stack')->getCurrentRequest();

    return [
      // Print the entire query string.
      '#markup' => $request->getQueryString(),
      '#cache' => [
        'contexts' => ['timezone', 'user'],
        'max-age' => 20,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path', 'url.query_args'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 30;
  }

}
