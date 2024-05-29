<?php

namespace Drupal\portland\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

class PortlandCommands extends DrushCommands {
  #[CLI\Command(name: 'portland:migrate-council-agendas')]
  #[CLI\Usage(
    name: 'drush portland:migrate-council-agendas',
    description: 'Creates the City Council committee term if it doesn\'t exist, and migrates all existing council agendas without a committee to the City Council term.',
  )]
  public function migrate_council_agendas() {
    // City Council term:
    // UUID 7e9d0dac-e638-4764-8dfc-23e9a4eb4118
    // TID 950

    $entityTypeManager = \Drupal::entityTypeManager();
    $term = $entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'uuid' => '7e9d0dac-e638-4764-8dfc-23e9a4eb4118',
      'vid' => 'council_committee',
      'tid' => 950,
    ]);
    if (sizeof($term) > 0) {
      $this->logger()->notice('City Council committee taxonomy term is already created. Skipping step.');
    } else {
      $this->logger()->notice('City Council committee taxonomy term not found. Creating new term with TID 950.');
      $term = $entityTypeManager->getStorage('taxonomy_term')->create([
        'uuid' => '7e9d0dac-e638-4764-8dfc-23e9a4eb4118',
        'vid' => 'council_committee',
        'tid' => 950,
        'name' => 'City Council',
        'field_prefix' => 'COUNCIL',
      ]);
      $term->path = ['alias' => '/council/agenda', 'pathauto' => 0];
      $term->save();
    }

    // Find all council agendas without a committee set
    $agendas = array_filter($entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'council_agenda',
    ]), fn($agenda) => $agenda->get('field_committee')->count() === 0);
    $this->logger()->notice(t('Migrating @count agendas.', [ '@count' => count($agendas) ]));
    foreach ($agendas as $agenda) {
      $agenda->set('field_committee', [['target_id' => 950]]);
      $agenda->setNewRevision(TRUE);
      $agenda->setRevisionLogMessage('Moved to "City Council" committee');
      $agenda->setRevisionCreationTime(\Drupal::time()->getCurrentTime());
      $agenda->setRevisionUserId(1);
      $agenda->save();
    }
  }
}
