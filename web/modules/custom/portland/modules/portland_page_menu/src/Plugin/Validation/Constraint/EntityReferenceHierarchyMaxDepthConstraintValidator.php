<?php

namespace Drupal\portland_page_menu\Plugin\Validation\Constraint;

use Drupal\entity_reference_hierarchy\EntityReferenceHierarchyFieldItemList;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the EntityReferenceHierarchyMaxDepth constraint.
 */
class EntityReferenceHierarchyMaxDepthConstraintValidator extends ConstraintValidator {
  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint): void {
    /** @var EntityReferenceHierarchyFieldItemList $field */
    if (!isset($field)) return;

    $items = $field->getValue();
    foreach($items as $delta => $item) {
      if ($item['depth'] > $constraint->max_depth) {
        $this->context
          ->buildViolation($constraint->message, ['%max_depth' => $constraint->max_depth])
          ->atPath((string) $delta)
          ->addViolation();
      }
    }
  }
}
