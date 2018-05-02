<?php

namespace Drupal\facets\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\BuildProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;

/**
 * Provides a processor for granularity.
 *
 * @FacetsProcessor(
 *   id = "granularity_item",
 *   label = @Translation("Granularity item processor"),
 *   description = @Translation("List of numbers grouped in steps."),
 *   stages = {
 *     "build" = 35
 *   }
 * )
 */
class GranularItemProcessor extends ProcessorPluginBase implements BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results) {
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'granularity' => 1,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $configuration = $this->getConfiguration();

    $build['granularity'] = [
      '#type' => 'number',
      '#attributes' => ['min' => 1, 'step' => 1],
      '#title' => $this->t('Granularity'),
      '#default_value' => $configuration['granularity'],
      '#description' => $this->t('The numeric size of the steps to group the result facets in.'),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType() {
    return 'numeric';
  }

}
