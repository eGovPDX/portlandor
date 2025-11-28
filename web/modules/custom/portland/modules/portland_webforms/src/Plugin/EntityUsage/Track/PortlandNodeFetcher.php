<?php

namespace Drupal\portland_webforms\Plugin\EntityUsage\Track;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_usage\EntityUsageTrackBase;
use Drupal\entity_usage\EntityUsageTrackMultipleLoadInterface;

/**
 * Dummy track plugin for portland_node_fetcher method.
 *
 * This plugin does not actively discover relationships; it exists so that
 * EntityUsageTrackManager recognizes the "portland_node_fetcher" method as a
 * valid plugin ID when processing URL updates. All actual usage rows for
 * Node Fetchers are registered manually via portland_webforms.module.
 *
 * @EntityUsageTrack(
 *   id = "portland_node_fetcher",
 *   label = @Translation("Portland Node Fetcher"),
 *   description = @Translation("Tracks manually-registered usages for Portland Node Fetcher elements."),
 *   field_types = {},
 *   source_entity_class = "Drupal\\Core\\Entity\\EntityInterface",
 * )
 */
class PortlandNodeFetcher extends EntityUsageTrackBase implements EntityUsageTrackMultipleLoadInterface {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $item): array {
    // Relationships are tracked manually via registerUsage(); nothing to
    // discover from fields here.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntitiesFromField(FieldItemListInterface $field): array {
    // Relationships are tracked manually via registerUsage(); nothing to
    // discover from fields here.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencedEntities(EntityInterface $entity): array {
    // No automatic references; this plugin exists only to satisfy the
    // track manager when updating URLs.
    return [];
  }

}
