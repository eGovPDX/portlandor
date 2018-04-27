<?php

namespace Drupal\bootstrap_layouts\Plugin\BootstrapLayouts;

use Drupal\bootstrap_layouts\BootstrapLayout;

/**
 * Handles Display Suite specific layout implementations.
 *
 * @BootstrapLayoutsHandler("page_manager")
 */
class PageManager extends BootstrapLayoutsHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function loadInstances(array $entity_ids = NULL) {
    $layouts = [];
    $properties = ['variant' => 'panels_variant'];
    if ($entity_ids) {
      $properties['id'] = $entity_ids;
    }
    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface[] $config_entities */
    $config_entities = $entity_type_manager->getStorage('page_variant')->loadByProperties($properties);
    foreach ($config_entities as $entity_id => $config_entity) {
      if (($info = $config_entity->get('variant_settings')) && isset($info['layout']) && isset($info['blocks'])) {
        $id = $info['layout'];

        // BootstrapLayout requires an associative array of "items" assigned to
        // a particular region, keyed by that region. Unfortunately, Page
        // Manager stores this value inside each block array; extract it.
        $regions = [];
        foreach ($info['blocks'] as $uuid => $block) {
          $regions[$block['region']][$uuid] = $block;
        }

        // Retrieve any layout settings.
        $settings = isset($info['layout_settings']) ? $info['layout_settings'] : [];

        // Create a new BootstrapLayout instance.
        $layouts[$entity_id] = new BootstrapLayout($id, $regions, $settings);
      }
    }
    return $layouts;
  }

  /**
   * {@inheritdoc}
   */
  public function saveInstances(array $layouts = []) {
    /** @var \Drupal\Core\Entity\EntityTypeManager $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface[] $config_entities */
    $config_entities = $entity_type_manager->getStorage('page_variant')
      ->loadByProperties(['variant' => 'panels_variant', 'id' => array_keys($layouts)]);

    /** @var \Drupal\bootstrap_layouts\BootstrapLayout[] $layouts */
    foreach ($layouts as $entity_id => $layout) {
      $config_entity = $config_entities[$entity_id];
      $info = $config_entity->get('variant_settings');
      $info['layout'] = $layout->getId();

      // The region is stored inside the block array. To effectively change
      // a region for a block, the variant's blocks must be iterated over and
      // changed manually based on the associative region key provided by the
      // BootstrapLayout instance.
      $info['blocks'] = [];
      foreach ($layout->getRegions() as $region => $blocks) {
        foreach ($blocks as $uuid => $block) {
          $block['region'] = $region;
          $info['blocks'][$uuid] = $block;
        }
      }

      $info['layout_settings'] = $layout->getSettings();
      $config_entity->set('variant_settings', $info)->save();
    }
  }

}
