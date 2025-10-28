<?php

namespace Drupal\portland_page_menu\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Ensures that the field does not reference its own parent entity.
 */
#[Constraint(
  id: 'EntityReferenceNoSelfReference',
  label: new TranslatableMarkup('Entity reference no self-reference', [], ['context' => 'Validation']),
  type: ['entity_reference', 'entity_reference_hierarchy'],
)]
class EntityReferenceNoSelfReferenceConstraint extends SymfonyConstraint {
  /**
   * The default violation message.
   */
  public string $message = 'This field cannot reference the parent entity (%target_id).';
}
