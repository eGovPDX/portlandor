<?php

namespace Drupal\bootstrap_layouts\Plugin\BootstrapLayouts;

use Drupal\bootstrap_layouts\BootstrapLayout;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Handles Display Suite specific layout implementations.
 *
 * @BootstrapLayoutsHandler("ds")
 */
class Ds extends BootstrapLayoutsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function loadInstances(array $entity_ids = NULL) {
    $layouts = [];
    /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $display */
    foreach (EntityViewDisplay::loadMultiple($entity_ids) as $entity_id => $display) {
      if (($info = $display->getThirdPartySettings('ds')) && isset($info['layout']['id'])) {
        $id = $info['layout']['id'];
        $regions = $info['regions'];
        $settings = $info['layout']['settings'];
        $path = isset($info['layout']['path']) ? $info['layout']['path'] : '';
        $layouts[$entity_id] = new BootstrapLayout($id, $regions, $settings, $path);
      }
    }
    return $layouts;
  }

  /**
   * {@inheritdoc}
   */
  public function saveInstances(array $layouts = []) {
    $displays = EntityViewDisplay::loadMultiple(array_keys($layouts));
    /** @var \Drupal\bootstrap_layouts\BootstrapLayout[] $layouts */
    foreach ($layouts as $entity_id => $layout) {
      /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $display */
      $display = $displays[$entity_id];
      $info = $display->getThirdPartySettings('ds');
      $info['layout']['id'] = $layout->getId();
      $info['regions'] = $layout->getRegions();
      $info['layout']['settings'] = $layout->getSettings();
      $info['layout']['path'] = $layout->getPath();

      // Unfortunately, there is no "setThirdPartySettings" method, so each
      // property must be iterated over manually, set and then saved.
      foreach ($info as $key => $value) {
        $display->setThirdPartySetting('ds', $key, $value);
      }
      $display->save();
    }
  }

}
