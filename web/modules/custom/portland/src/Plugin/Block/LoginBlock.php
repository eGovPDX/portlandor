<?php

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Creates a login link that redirects back to the current page after login,
 * using the destination url parameter.
 *
 * @Block(
 *   id = "portland_login_block",
 *   admin_label = @Translation("Portland Login Block"),
 *
 * )
 */
class LoginBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function getCacheMaxAge() {
      return 0;
    }

    /**
     * {@inheritdoc}
     * 
     * Simple block that outputs a login link decorated with a return url.
     */
    public function build() {
      $destination = \Drupal::service('path.current')->getPath();
      $render_array = [
        '#markup' => '<a href="/user/login?destination=' . $destination . '">Editor log in</a>'
      ];
      return $render_array;
    }
}