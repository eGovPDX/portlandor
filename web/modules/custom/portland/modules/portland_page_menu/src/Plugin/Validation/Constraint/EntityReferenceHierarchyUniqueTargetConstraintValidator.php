<?php

namespace Drupal\portland_page_menu\Plugin\Validation\Constraint;

use Drupal\entity_reference_hierarchy\EntityReferenceHierarchyFieldItemList;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityReferenceHierarchyUniqueTarget constraint.
 */
class EntityReferenceHierarchyUniqueTargetConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new validator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint): void {
    /** @var EntityReferenceHierarchyFieldItemList $field */
    if (!isset($field)) return;

    $entity = $field->getEntity();
    $field_name = $field->getName();
    $field_label = $field->getFieldDefinition()->getLabel();
    $target_ids = array_column($field->getValue(), 'target_id');
    if (empty($target_ids)) return;

    // Get all entities of the same type+bundle that have the same field containing any of the target IDs.
    $entity_type = $entity->getEntityType();
    $storage = $this->entityTypeManager->getStorage($entity_type->id());
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition($entity_type->getKey('bundle'), $entity->bundle())
      ->condition($field_name, $target_ids, 'IN');
    // Exclude the current entity from the check.
    if (!$entity->isNew()) {
      $query->condition($entity_type->getKey('id'), $entity->id(), '!=');
    }

    $result = $query->execute();
    foreach ($result as $other_entity_id) {
      $other_entity = $storage->load($other_entity_id);
      if ($other_entity instanceof FieldableEntityInterface && $other_entity->hasField($field_name)) {
        $other_target_ids = array_column($other_entity->get($field_name)->getValue(), 'target_id');
        foreach ($other_target_ids as $other_target_id) {
          $delta = array_search($other_target_id, $target_ids);
          if ($delta === false) continue;

          $this->context
            ->buildViolation($constraint->message, [
              '@field_label' => $field_label,
              '%target_id' => $other_target_id,
              '%conflict_label' => $other_entity->label(),
              '%conflict_target_id' => $other_entity->id(),
              ':conflict_url' => $other_entity->toUrl()->toString(),
            ])
            ->atPath((string) $delta)
            ->addViolation();
        }
      }
    }
  }
}
