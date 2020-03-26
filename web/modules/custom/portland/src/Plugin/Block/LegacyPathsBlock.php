<?php
/**
 * @file
 * Contains \portland\Plugin\Block\SearchBlock
 */

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a Portland legacy paths block.
 *
 * @Block(
 *   id = "portland_legacy_paths_block",
 *   admin_label = @Translation("Portland Legacy Paths Block"),
 *
 * )
 */
class LegacyPathsBlock extends BlockBase {
    private static $pog_base_url = "https://www.portlandoregon.gov/";
    private static $help_text = "The content above replaces the following pages on the old portlandoregon.gov website. When activated, requests for these paths on the old site will redirect here on the new site. Use the links below to test that the legacy paths are correct.";

    /**
     * {@inheritdoc}
     */
    public function getCacheMaxAge() {
      return 0;
    }

    /**
     * {@inheritdoc}
     * 
     * This block only ever gets displayed in edit mode, due to block placement settings.
     */
    public function build() {
      // if this is a node or group, get the entity object
      $entity = \Drupal::routeMatch()->getParameter('node');
      if (!isset($entity)) {
        $entity = \Drupal::routeMatch()->getParameter('group');
      }

      // if node or group, look up any legacy paths/redirects and pass to template.
      if (isset($entity)) {
        $nid = $entity->Id();
        $type = $entity->getEntityTypeId();
        if ($nid && $type) {
          $redirects = \Drupal::service('redirect.repository')->findByDestinationUri(["internal:/$type/$nid", "entity:$type/$nid"]);
        }
        $legacy_paths = [];
        foreach ($redirects as $redirect) {
          $legacy_paths[] = $redirect->getSource()['path'];
        }

        $render_array = [
          '#theme' => 'portland_legacy_paths_block',
          '#pog_base_url' => self::$pog_base_url,
          '#legacy_paths' => $legacy_paths,
          '#help_text' => t(self::$help_text),
        ];

        return $render_array;
      }

      // not a node or group
      return null;
    }

}