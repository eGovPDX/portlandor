<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Make sure the Updated date/time is after the Published date/time.
 *
 * @Constraint(
 *   id = "NewsDateTime",
 *   label = @Translation("Make sure Updated date/time is newer than Published start date/time.", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class NewsDateTimeConstraint extends CompositeConstraintBase {

  /**
   * Message shown when Updated date/time is before Published date/time.
   *
   * @var string
   */
  public $errorMessage = 'Updated date/time must be after Published date/time.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['field_updated_on', 'field_published_on'];
  }
}
