<?php

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;

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
      $login_link = "";
      if ($logged_in) {
        $options = [
          'query' => [
            'destination' => $request->query->get("destination"),
          ],
          'absolute' => TRUE,
        ];
        $logout_url = Url::fromRoute('user.logout', [], $options)->toString();
        $login_text = $this->t('Editor log out');
        $login_link = "<a href=\"$logout_url\">" . $login_text . "</a>";
      } else {
        $query_string = http_build_query($request->query->all());
        $login_text = $this->t('Editor log in');
        $login_link = "<a href=\"/user/login?$query_string\">$login_text</a>";
      }
      
      $about_website = $this->t('About this website')->__toString();
      $employee_portal = $this->t('Employee portal')->__toString();
      
      $render_array = [
        '#markup' => '<ul class="menu">
          <li class="menu-item"><a href="' . Url::fromRoute('entity.node.canonical', ['node' => 6008])->toString() . '">' . $about_website . '</a></li>
          <li class="menu-item"><a href="https://employees.portland.gov/">' . $employee_portal . '</a></li>
          <li class="menu-item">' . $login_link . '</li>
        </ul>',
      ];
      return $render_array;
    }
}