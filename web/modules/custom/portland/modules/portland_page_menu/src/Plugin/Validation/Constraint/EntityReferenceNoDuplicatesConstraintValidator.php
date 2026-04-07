<?php

namespace Drupal\portland_page_menu\Plugin\Validation\Constraint;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityReferenceNoDuplicates constraint.
 */
class EntityReferenceNoDuplicatesConstraintValidator extends ConstraintValidator {
  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint): void {
    /** @var EntityReferenceFieldItemListInterface $field */
    if (!isset($field)) return;

    $target_ids = array_column($field->getValue(), 'target_id');
    if (empty($target_ids)) return;

    foreach (array_count_values($target_ids) as $target_id => $count) {
      if ($count > 1) {
        // Reverse array so the error is added to the last duplicate entry.
        $delta = array_search($target_id, array_reverse($target_ids, true));
        if ($delta === false) continue;

        $this->context
          ->buildViolation($constraint->message, [
            '%target_id' => $target_id,
          ])
          ->atPath((string) $delta)
          ->addViolation();
      }
    }
  }
}
