<?php

namespace Drupal\portland_webforms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\entity_usage\EntityUsageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Safe override of Entity Usage listing to handle config source entities.
 *
 * Avoids LogicException when source_type is a config entity (like webform) by
 * skipping field definition lookups for non-content entities.
 */
class WebformSafeListUsageController extends ControllerBase {

  /**
   * The entity usage service.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface
   */
  protected EntityUsageInterface $entityUsage;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * Constructs a WebformSafeListUsageController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_usage\EntityUsageInterface $entity_usage
   *   The entity usage service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityUsageInterface $entity_usage,
    EntityFieldManagerInterface $entity_field_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityUsage = $entity_usage;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('entity_usage.usage'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Page callback: list usage of an entity.
   *
   * @param string $entity_type_id
   *   The target entity type ID.
   * @param string $entity_id
   *   The target entity ID.
   *
   * @return array
   *   A render array.
   */
  public function listUsagePage(string $entity_type_id, string $entity_id): array {
    $build = [];
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Source entity'),
        $this->t('Field / Context'),
        $this->t('Method'),
      ],
      '#rows' => $this->getRows($entity_type_id, $entity_id),
      '#empty' => $this->t('No usages found.'),
    ];
    return $build;
  }

  /**
   * Builds rows of usages, skipping field definitions for config entities.
   *
   * @param string $entity_type_id
   *   The target entity type ID.
   * @param string $entity_id
   *   The target entity ID.
   *
   * @return array
   *   An array of table rows.
   */
  protected function getRows(string $entity_type_id, string $entity_id): array {
    $rows = [];
    
    // Load the target entity to get sources.
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $entity = $storage->load($entity_id);
    
    if (!$entity) {
      return $rows;
    }

    $sources = $this->entityUsage->listSources($entity);

    foreach ($sources as $source_type => $source_entities) {
      foreach ($source_entities as $source_id => $usage_data) {
        $field_label = NULL;

        // Only attempt field definitions for content entity source types.
        $source_def = $this->entityTypeManager->getDefinition($source_type, FALSE);
        $is_content = $source_def && is_subclass_of($source_def->getClass(), ContentEntityInterface::class);

        if ($is_content && !empty($usage_data[0]['field_name'])) {
          try {
            $field_definitions = $this->entityFieldManager->getFieldDefinitions($source_type, $source_type);
            $field_name = $usage_data[0]['field_name'];
            if (isset($field_definitions[$field_name])) {
              $field_label = $field_definitions[$field_name]->getLabel();
            }
          }
          catch (\LogicException $e) {
            // Ignore for config entities or other errors.
            $field_label = NULL;
          }
        }

        $rows[] = [
          ['data' => $this->buildEntityLink($source_type, $source_id)],
          ['data' => $field_label ?? ($usage_data[0]['field_name'] ?? '')],
          ['data' => $usage_data[0]['method'] ?? ''],
        ];
      }
    }

    return $rows;
  }

  /**
   * Builds a link to the source entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string|int $entity_id
   *   The entity ID.
   *
   * @return array
   *   A render array for the table cell.
   */
  protected function buildEntityLink(string $entity_type_id, string|int $entity_id): array {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entity = $storage->load($entity_id);
      if ($entity && $entity->hasLinkTemplate('canonical')) {
        $link = $entity->toLink();
        return [
          '#type' => 'link',
          '#title' => $link->getText(),
          '#url' => $link->getUrl(),
        ];
      }
      elseif ($entity) {
        return ['#plain_text' => $entity->label()];
      }
    }
    catch (\Exception $e) {
      // Entity type or storage not available.
    }
    return ['#plain_text' => (string) $this->t('(Deleted or unavailable)')];
  }

  /**
   * Checks access for the usage page.
   *
   * @param string $entity_type_id
   *   The target entity type ID.
   * @param string $entity_id
   *   The target entity ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(string $entity_type_id, string $entity_id): \Drupal\Core\Access\AccessResultInterface {
    return \Drupal\Core\Access\AccessResult::allowedIfHasPermission(
      \Drupal::currentUser(),
      'access entity usage statistics'
    );
  }

  /**
   * Gets the page title.
   *
   * @param string $entity_type_id
   *   The target entity type ID.
   * @param string $entity_id
   *   The target entity ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function getTitle(string $entity_type_id, string $entity_id): \Drupal\Core\StringTranslation\TranslatableMarkup {
    return $this->t('Usage for @entity', ['@entity' => $entity_type_id . ' ' . $entity_id]);
  }

}
