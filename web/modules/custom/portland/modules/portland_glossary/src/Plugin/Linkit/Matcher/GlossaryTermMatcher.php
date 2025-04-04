<?php

namespace Drupal\portland_glossary\Plugin\Linkit\Matcher;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\linkit\MatcherInterface;
use Drupal\linkit\Suggestion\Suggestion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a LinkIt matcher for Glossary Term nodes.
 *
 * @Matcher(
 *   id = "glossary_term_matcher",
 *   label = @Translation("Glossary Term Matcher"),
 *   target_entity = "node"
 * )
 */
class GlossaryTermMatcher extends PluginBase implements MatcherInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = [];

    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'glossary_term')
      ->condition('title', $string, 'CONTAINS')
      ->range(0, 100)
      ->accessCheck(TRUE)
      ->execute();

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);

    foreach ($nodes as $node) {
      $suggestion = new Suggestion();
      $suggestion->setLabel($node->label());
      $suggestion->setPath('/node/' . $node->id());
      $suggestion->setGroup('Glossary Terms');
      $suggestion->setDescription($node->toUrl()->toString() . ' (glossary_term)');
      $suggestion->setExtraAttributes([
        'class' => 'glossary-term',
      ]);
      $suggestions[] = $suggestion;
    }

    return $suggestions;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->configuration['uuid'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['label'] ?? 'Glossary Term Matcher';
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return $this->t('Matches glossary_term nodes, adds class="glossary-term".');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->configuration['weight'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->configuration['weight'] = $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    return 'node';
  }
}
