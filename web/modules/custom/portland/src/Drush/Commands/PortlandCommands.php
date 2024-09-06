<?php

namespace Drupal\portland\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

class PortlandCommands extends DrushCommands {
  #[CLI\Command(name: 'portland:migrate-council-budget-analysis')]
  #[CLI\Usage(
    name: 'drush portland:migrate-council-budget-analysis',
    description: 'Creates the Budget Office Financial Impact Analysis impact statement type if it doesn\'t exist, and migrates all existing field_finanicial_impact_analysis to a Council Impact Statement relation.',
  )]
  public function migrate_council_budget_analysis() {
    // City Council term:
    // UUID 7e9d0dac-e638-4764-8dfc-23e9a4eb4118
    // TID 950

    $entityTypeManager = \Drupal::entityTypeManager();
    $term_storage = $entityTypeManager->getStorage('taxonomy_term');
    $term = $term_storage->loadByProperties([
      'name' => 'Budget Office Financial Impact Analysis',
    ]);
    if (sizeof($term) > 0) {
      $this->logger()->notice('Budget Office Financial Impact Analysis impact statement type is already created. Skipping step.');
      $term = reset($term);
    } else {
      $this->logger()->notice('Budget Office Financial Impact Analysis impact statement type not found. Creating new term.');
      $term = $term_storage->create([
        'vid' => 'council_impact_statement_type',
        'name' => 'Budget Office Financial Impact Analysis',
      ]);
      $term->save();
    }

    // Find all council documents with a budget analysis field
    $node_storage = $entityTypeManager->getStorage('node');
    $nids = $node_storage
      ->getQuery()
      ->accessCheck(false)
      ->condition('type', 'council_document')
      ->condition('field_finanicial_impact_analysis', '', 'IS NOT NULL')
      ->execute();
    $documents = $node_storage->loadMultiple($nids);
    $this->logger()->notice(t('Migrating @count documents.', [ '@count' => count($documents) ]));

    $relation_storage = $entityTypeManager->getStorage('relation');
    foreach ($documents as $document) {
      $budget_analysis = $document->field_finanicial_impact_analysis[0];
      $budget_analysis->set('format', 'simple_editor');
      $relation = $relation_storage->create([
        'type' => 'council_impact_statement',
        'field_body_content' => $budget_analysis,
        'field_council_document' => [['target_id' => $document->id()]],
        'field_impact_statement_type' => ['target_id' => $term->id()],
      ]);
      $relation->save();
      $document->set('field_finanicial_impact_analysis', NULL);
      $document->setNewRevision(TRUE);
      $document->setRevisionLogMessage("Migrated Budget Office Financial Impact Analysis to new impact statement system (relation ID {$relation->id()})");
      $document->setRevisionCreationTime(\Drupal::time()->getCurrentTime());
      $document->setRevisionUserId(1);
      $document->save();
      $this->logger()->notice(t('Migrated NID @id.', [ '@id' => $document->id() ]));
    }
  }
}
