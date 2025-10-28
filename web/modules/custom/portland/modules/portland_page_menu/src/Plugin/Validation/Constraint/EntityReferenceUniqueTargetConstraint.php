<?php

namespace Drupal\portland_page_menu\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Ensures that a referenced entity is only referenced once across the site for this bundle+field combo.
 */
#[Constraint(
  id: 'EntityReferenceUniqueTarget',
  label: new TranslatableMarkup('Entity reference unique target', [], ['context' => 'Validation']),
  type: ['entity_reference', 'entity_reference_hierarchy'],
)]
class EntityReferenceUniqueTargetConstraint extends SymfonyConstraint {
  /**
   * The default violation message.
   */
  public string $message = 'The entity %target_id is already referenced in another @field_label field from <a href=":conflict_url">%conflict_label (%conflict_target_id)</a>.';
}
