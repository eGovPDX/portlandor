<?php

namespace Drupal\portland_ecouncil\Plugin\views\filter;

use Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View filter for determining whether a council document needs or has an associated impact statement of a certain type
 *
 * @ViewsFilter("node_council_document_impact_analysis")
 */
class NodeCouncilDocumentImpactAnalysis extends TaxonomyIndexTid implements ContainerFactoryPluginInterface {
  protected Connection $database;
  protected EntityTypeManagerInterface $entityTypeManager;


  public function __construct(array $configuration, $plugin_id, $plugin_definition, VocabularyStorageInterface $vocabulary_storage, TermStorageInterface $term_storage, AccountInterface $current_user, Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $vocabulary_storage, $term_storage, $current_user);

    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Limit the operators available from the parent class to only those which make sense for this filter.
   */
  public function operators() {
    $operators = [
      'not' => [
        'title' => $this->t('Needs analysis'),
        'short' => $this->t('not'),
        'short_single' => $this->t('<>'),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ],
      'or' => [
        'title' => $this->t('Has analysis'),
        'short' => $this->t('or'),
        'short_single' => $this->t('='),
        'method' => 'opHelper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ],
    ];

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query_base_table = $this->relationship ?: $this->view->storage->get('base_table');
    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    $entity_id_key = $entity_type->getKeys()['id'];

    // `value` is an array of the TIDs selected in the filter settings.
    $tids = $this->value;

    // Create a sub-query to find all Council Impact Statements associated to the node by field_council_document,
    // filtered to those with a field_impact_statement_type that is one of the taxonomy terms selected by the filter.
    $subselect = $this->database
      ->select('relation__field_council_document', 'r')
      ->fields('r', ['entity_id']);
    $subselect->leftJoin('relation__field_impact_statement_type', 'type', "type.entity_id = r.entity_id AND type.deleted = '0'");
    $subselect
      ->where("r.deleted = '0' AND r.field_council_document_target_id = $query_base_table.$entity_id_key")
      ->condition('type.field_impact_statement_type_target_id', $tids, 'IN');

    // Create a new condition to check whether out above query is empty or has results,
    // depending on the mode of the filter.
    $condition = $this->database->condition('AND');
    if ($this->operator === 'or') {
      // Has existing analysis
      $condition = $condition->exists($subselect);
    } else if ($this->operator === 'not') {
      // Needs analysis
      $condition = $condition->notExists($subselect);
    }
    // Add our condition to the root query.
    $this->query->addWhere($this->options['group'], $condition);
  }
}
