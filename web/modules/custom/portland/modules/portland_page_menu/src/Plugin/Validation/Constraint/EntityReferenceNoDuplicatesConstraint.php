<?php

namespace Drupal\portland_page_menu\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Ensures that a referenced entity is only referenced once in this field.
 */
#[Constraint(
  id: 'EntityReferenceNoDuplicates',
  label: new TranslatableMarkup('Entity reference no duplicates', [], ['context' => 'Validation']),
  type: ['entity_reference', 'entity_reference_hierarchy'],
)]
class EntityReferenceNoDuplicatesConstraint extends SymfonyConstraint {
  /**
   * The default violation message.
   */
  public string $message = 'The entity %target_id is referenced multiple times in this field.';
}
