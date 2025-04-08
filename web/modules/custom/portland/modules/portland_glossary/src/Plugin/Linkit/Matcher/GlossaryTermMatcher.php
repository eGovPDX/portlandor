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
      $suggestion->setDescription($node->toUrl()->toString());
      $suggestion->setExtraAttributes([
        'class' => 'glossary-term',
      ]);
      $suggestions[] = $suggestion;
    }

    return $suggestions;
  }

  public function getUuid() {
    return $this->configuration['uuid'] ?? NULL;
  }

  public function getPluginId() {
    return $this->pluginId;
  }

  public function getLabel() {
    return $this->getPluginDefinition()['label'];
  }

  public function getSummary() {
    return $this->t('Matches Glossary Term nodes.');
  }

  public function getWeight() {
    return $this->configuration['weight'] ?? 0;
  }

  public function setWeight($weight) {
    $this->configuration['weight'] = $weight;
  }

  public function getConfiguration() {
    return $this->configuration;
  }

  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  public function defaultConfiguration() {
    return [];
  }

  public function calculateDependencies() {
    return [];
  }

  public function getTarget() {
    return 'node';
  }
}
