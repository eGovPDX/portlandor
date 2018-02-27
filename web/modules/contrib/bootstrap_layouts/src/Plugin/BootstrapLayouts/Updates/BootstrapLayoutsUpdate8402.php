<?php

namespace Drupal\bootstrap_layouts\Plugin\BootstrapLayouts\Updates;

use Drupal\bootstrap_layouts\BootstrapLayout;
use Drupal\bootstrap_layouts\Plugin\BootstrapLayouts\BootstrapLayoutsUpdateBase;

/**
 * Bootstrap Layouts Update 8402
 *
 * Fix "1 Column (stacked)" regions.
 *
 * @BootstrapLayoutsUpdate(
 *   id = "bootstrap_layouts_update_8402",
 *   schema = 8402
 * )
 */
class BootstrapLayoutsUpdate8402 extends BootstrapLayoutsUpdateBase {

  /**
   * {@inheritdoc}
   */
  public function processExistingLayout(BootstrapLayout $layout, array $data = [], $display_messages = TRUE) {
    if ($layout->getId() !== 'bs_1col_stacked') {
      return;
    }

    $regions = [
      'header' => 'top',
      'footer' => 'bottom',
    ];
    foreach ($regions as $old_region => $new_region) {
      if ($region = $layout->getRegion($old_region)) {
        $layout->setRegion($new_region, $region);
        $layout->unsetRegion($old_region);
      }
    }
  }

}
