<?php
namespace Drupal\lightning_core\Commands;

use Drupal\lightning_core\UpdateManager;
use Drush\Commands\DrushCommands;
use Drush\Style\DrushStyle;

class LightningCoreCommands extends DrushCommands {

  /**
   * The update manager service.
   *
   * @var \Drupal\lightning_core\UpdateManager
   */
  protected $updateManager;

  /**
   * LightningCoreCommands constructor.
   *
   * @param \Drupal\lightning_core\UpdateManager $update_manager
   *   The update manager service.
   */
  public function __construct(UpdateManager $update_manager) {
    $this->updateManager = $update_manager;
  }

  /**
   * Executes Lightning configuration updates from a specific version.
   *
   * @command update:lightning
   *
   * @usage update:lightning
   *   Runs all available configuration updates.
   */
  public function update() {
    $io = new DrushStyle($this->input(), $this->output());
    $this->updateManager->executeAllInConsole($io);
  }

}
