<?php

namespace Drupal\portland_page_menu\Plugin\Validation\Constraint;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityReferenceNoSelfReference constraint.
 */
class EntityReferenceNoSelfReferenceConstraintValidator extends ConstraintValidator {
  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint): void {
    /** @var EntityReferenceFieldItemListInterface $field */
    if (!isset($field)) return;

    $entity = $field->getEntity();
    if (!isset($entity) || $entity->isNew()) return;

    $target_ids = array_column($field->getValue(), 'target_id');
    if (in_array($entity->id(), $target_ids)) {
      $delta = array_search($entity->id(), $target_ids, true);

      $this->context
        ->buildViolation($constraint->message, [
          '%target_id' => $entity->id(),
        ])
        ->atPath((string) $delta)
        ->addViolation();
    }
  }
}
