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
      $logged_in = \Drupal::currentUser()->isAuthenticated();
      $request = \Drupal::request();
      $destination = $request->query->get("destination");
      // If destination is not in the query string, set it to current path
      if(empty($destination)) {
        $request->query->set("destination", $request->getPathInfo());
      }
      $query_string = http_build_query($request->query->all());
      $markup = "";
      if ($logged_in) {
        $markup = '<a href="/user/logout?' . $query_string . '">Editor log out</a>';
      } else {
        $markup = '<a href="/user/login?' . $query_string . '">Editor log in</a>';
      }
      $render_array = [
        '#markup' => $markup
      ];
      return $render_array;
    }
}