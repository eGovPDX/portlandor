<?php

namespace Drupal\actions_permissions;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Create permissions for existing actions.
 */
class ActionsPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * VBO Action manager service.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager
   */
  protected $actionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   The action manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(ViewsBulkOperationsActionManager $actionManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->actionManager = $actionManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get permissions for Actions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();

    // Get definitions that will not be altered by actions_permissions.
    foreach ($this->actionManager->getDefinitions([
      'skip_actions_permissions' => TRUE,
      'nocache' => TRUE,
    ]) as $definition) {

      // Skip actions that define their own requirements.
      if (!empty($definition['requirements'])) {
        continue;
      }

      $id = 'execute ' . $definition['id'];
      $entity_type = NULL;
      if (empty($definition['type'])) {
        $entity_type = $this->t('all entity types');
        $id .= ' all';
      }
      elseif (isset($entity_type_definitions[$definition['type']])) {
        $entity_type = $entity_type_definitions[$definition['type']]->getLabel();
        $id .= ' ' . $definition['type'];
      }

      if (isset($entity_type)) {
        $permissions[$id] = [
          'title' => $this->t('Execute the %action action on %type.', [
            '%action' => $definition['label'],
            '%type' => $entity_type,
          ]),
        ];
      }
    }

    // Rebuild VBO action definitions cache with
    // included action_permissions modifications.
    $this->actionManager->getDefinitions([
      'nocache' => TRUE,
    ]);

    return $permissions;
  }

}
