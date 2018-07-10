<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Make sure end date/time is after start date/time.
 *
 * @Constraint(
 *   id = "EventDateTime",
 *   label = @Translation("Make sure end date/time is after start date/time.", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class EventDateTimeConstraint extends CompositeConstraintBase {

  /**
   * Message shown when end date is before start date.
   *
   * @var string
   */
  public $message = 'End date/time must be after the start date/time.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['field_start_date', 'field_start_tiem', 'field_end_date', 'field_end_time'];
  }
}