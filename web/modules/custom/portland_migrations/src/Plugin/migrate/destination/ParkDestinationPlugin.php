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
    $entity_id = reset($destination_identifier);

    $entity = $this->storage->load($entity_id);
    // Delete attached files on Working Papers.
    if ($entity && $entity->bundle() === 'park_facility') {
      $address = $entity->get('field_location')->entity;
      if( $address != NULL ) $address->delete();
      echo 'address deleted';

      $documents = $entity->get('field_documents')->referencedEntities();
      foreach($documents as $document) {
        $document->get('field_document')->entity->delete();
        echo 'document deleted';
      }

      $images = $entity->get('field_images')->referencedEntities();
      foreach($images as $image) {
        $image->get('image')->entity->delete();
        echo 'image deleted';
      }
    }
    // Execute the normal rollback from here.
    parent::rollback($destination_identifier);
  }
}