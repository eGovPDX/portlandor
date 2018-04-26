<?php

namespace Drupal\facets\Processor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\facets\Annotation\FacetsProcessor;

/**
 * Manages processor plugins.
 *
 * @see \Drupal\facets\Annotation\FacetsProcessor
 * @see \Drupal\facets\Processor\ProcessorInterface
 * @see \Drupal\facets\Processor\ProcessorPluginBase
 * @see plugin_api
 */
class ProcessorPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, TranslationInterface $translation) {
    parent::__construct('Plugin/facets/processor', $namespaces, $module_handler, ProcessorInterface::class, FacetsProcessor::class);
    $this->setCacheBackend($cache_backend, 'facets_processors');
    $this->setStringTranslation($translation);
  }

  /**
   * Retrieves information about the available processing stages.
   *
   * These are then used by processors in their "stages" definition to specify
   * in which stages they will run.
   *
   * @return array
   *   An associative array mapping stage identifiers to information about that
   *   stage. The information itself is an associative array with the following
   *   keys:
   *   - label: The translated label for this stage.
   */
  public function getProcessingStages() {
    return [
      ProcessorInterface::STAGE_PRE_QUERY => [
        'label' => $this->t('Pre query stage'),
      ],
      ProcessorInterface::STAGE_POST_QUERY => [
        'label' => $this->t('Post query stage'),
      ],
      ProcessorInterface::STAGE_BUILD => [
        'label' => $this->t('Build stage'),
      ],
      ProcessorInterface::STAGE_SORT => [
        'label' => $this->t('Sort stage'),
      ],
    ];
  }

}
