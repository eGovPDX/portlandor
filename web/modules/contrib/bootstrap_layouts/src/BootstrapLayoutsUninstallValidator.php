<?php

namespace Drupal\bootstrap_layouts;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class BootstrapLayoutsUninstallValidator
 */
class BootstrapLayoutsUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The BootstrapLayouts manager.
   *
   * @var \Drupal\bootstrap_layouts\BootstrapLayoutsManager
   */
  protected $manager;

  /**
   * Constructs a new ContentUninstallValidator.
   *
   * @param \Drupal\bootstrap_layouts\BootstrapLayoutsManager $manager
   *   The BootstrapLayouts manager.
   */
  public function __construct(BootstrapLayoutsManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];

    if ($module === 'bootstrap_layouts') {
      $layouts = [];
      foreach ($this->manager->getHandlers() as $handler) {
        foreach ($handler->loadInstances() as $storage_id => $layout) {
          if ($layout->isBootstrapLayout()) {
            $layouts[$layout->getId()][] = $handler->getPluginId() . ':' . $storage_id;
          }
        }
      }
      ksort($layouts);
      foreach ($layouts as $layout_id => $storage_ids) {
        sort($storage_ids, SORT_NATURAL);
        $reasons[] = $this->t('Using layout: @layout_id (@storage_ids)', [
          '@layout_id' => $layout_id,
          '@storage_ids' => implode(', ', $storage_ids),
        ]);
      }
    }

    return $reasons;
  }

}
