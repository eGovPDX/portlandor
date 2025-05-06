<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Limit the max depth of an entity reference hierarchy field.
 */
#[Constraint(
  id: 'EntityReferenceHierarchyMaxDepth',
  label: new TranslatableMarkup('Entity reference hierarchy max depth', [], ['context' => 'Validation']),
  type: ['entity_reference_hierarchy'],
)]
class EntityReferenceHierarchyMaxDepthConstraint extends SymfonyConstraint {
  /**
   * The default violation message.
   */
  public string $message = 'The maximum depth is %max_depth.';

  /**
   * The max depth option.
   */
  public int $max_depth;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'max_depth';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['max_depth'];
  }
}
