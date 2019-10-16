<?php

namespace Drupal\portland_migrations\Plugin\migrate\destination;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;

/**
 * Delete the associated location when roll back parks migration.
 *  
 * @MigrateDestination(
 *   id = "park_destination_plugin"
 * )
 */
class ParkDestinationPlugin extends EntityContentBase {
  /** @var string $entityType */
  public static $entityType = 'node';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return parent::create($container, $configuration, 'entity:' . static::$entityType, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $entity = $this->storage->load(reset($destination_identifier));

    // Delete attached files on Working Papers.
    if ($entity && $entity instanceof NodeInterface) {
      if ($entity->bundle() === 'park_facility') {
        \Drupal::entityTypeManager()->getStorage("node")->delete([$entity->get('field_park_address_or_entrance')->referencedEntities()]);
      }
    }

    // Execute the normal rollback from here.
    parent::rollback($destination_identifier);
  }
}