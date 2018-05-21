<?php

namespace Drupal\lightning_media\Update;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Update("2.0.0")
 */
final class Update200 implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Update200 constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   (optional) The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TranslationInterface $translation = NULL) {
    $this->configFactory = $config_factory;

    if ($translation) {
      $this->setStringTranslation($translation);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('string_translation')
    );
  }

  /**
   * Renames the Media overview page's Source filter.
   *
   * @update
   *
   * @ask Do you want to rename the "Media Type" filter label on the Media
   * overview page to "Type"? This will also change the filter's URL identifier
   * to 'type' and will make the label and URL identifier consistent with new
   * installs of Lightning.
   */
  public function renameMediaOverviewTypeFilter() {
    $view = $this->configFactory->getEditable('views.view.media');

    if ($view->isNew()) {
      return;
    }

    $property = 'display.default.display_options.filters.bundle';

    $filter = $view->get($property);
    if ($filter) {
      $filter['expose']['label'] = 'Type';
      $filter['expose']['identifier'] = 'type';

      $view->set($property, $filter)->save();
    }
  }

}
