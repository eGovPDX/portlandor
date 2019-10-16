<?php

namespace Drupal\mem_migrate_oembed\Commands;

use Drupal\mem_migrate_oembed\MemMigrate;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class MemCommands extends DrushCommands {

  /**
   * The migrate service.
   *
   * @var \Drupal\mem_migrate_oembed\MemMigrate
   */
  protected $migrator;

  /**
   * SamplerCommands constructor.
   *
   * @param \Drupal\mem_migrate_oembed\MemMigrate $migrator
   *   The migrate service.
   */
  public function __construct(MemMigrate $migrator) {
    parent::__construct();
    $this->migrator = $migrator;
  }

  /**
   * Migrates from media_embed_media to core media.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command mem:migrate_oembed
   */
  public function migrate() {
    $this->migrator->migrate();
  }

}
