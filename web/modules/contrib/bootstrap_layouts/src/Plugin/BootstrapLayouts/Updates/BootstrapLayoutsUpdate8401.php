<?php

namespace Drupal\bootstrap_layouts\Plugin\BootstrapLayouts\Updates;

use Drupal\bootstrap_layouts\BootstrapLayout;
use Drupal\bootstrap_layouts\Plugin\BootstrapLayouts\BootstrapLayoutsUpdateBase;

/**
 * Bootstrap Layouts Update 8401
 *
 * Upgrade existing Bootstrap Layout instances.
 *
 * @BootstrapLayoutsUpdate(
 *   id = "bootstrap_layouts_update_8401",
 *   schema = 8401
 * )
 */
class BootstrapLayoutsUpdate8401 extends BootstrapLayoutsUpdateBase {

  /**
   * {@inheritdoc}
   */
  public function processExistingLayout(BootstrapLayout $layout, array $data = [], $display_messages = TRUE) {
    // Fix any typos and replace hyphens with underscores.
    $id = preg_replace('/\-+/', '_', preg_replace('/^booststrap/', 'bootstrap', $layout->getId()));

    // Immediately return if existing layout identifier doesn't match
    // one of the old "bootstrap_layouts" layouts.
    if (!isset($data['bootstrap_layouts_update_map'][$id])) {
      return;
    }

    $layout_map = $data['bootstrap_layouts_update_map'][$id];

    // Set the new layout identifier.
    $layout->setId($layout_map['id']);

    // Only update the path if it's actually set.
    $path = $layout->getPath();
    if (isset($path)) {
      $layout->setPath($this->getPath() . '/templates/3.0.0');
    }

    // Set default layout wrapper, attributes and classes.
    $layout->setSetting('layout.wrapper', 'div');
    $layout->setSetting('layout.classes', ['row', 'clearfix']);
    $layout->setSetting('layout.attributes', '');

    // Rename existing region and set region wrapper, attributes and classes.
    foreach ($layout_map['regions'] as $old_region => $new_region) {
      if ($old_region !== $new_region && ($region_data = $layout->getRegion($old_region))) {
        $layout->setRegion($new_region, $region_data);
        $layout->unsetRegion($old_region);
      }
      $layout->setSetting("regions.$new_region.wrapper", 'div');
      $layout->setSetting("regions.$new_region.classes", $layout_map['classes'][$new_region]);
      $layout->setSetting("regions.$new_region.attributes", '');
    }
  }

}
