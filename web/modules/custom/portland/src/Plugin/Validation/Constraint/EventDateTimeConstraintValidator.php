<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PreventAnon constraint.
 */
class EventDateTimeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity->bundle() != 'event') {
      return;
    }

    // Unset the end date if it's the same as start date.
    if(isset($entity->field_end_date->value) &&
        $entity->field_end_date->value == $entity->field_start_date->value) {
      unset($entity->field_end_date->value);
    }

    // If the end date is the same as start date, we compare start and end time.
    if( ! isset($entity->field_end_date->value) && 
        isset($entity->field_end_time->value) &&
        isset($entity->field_start_time->value) && 
        $entity->field_end_time->value < $entity->field_start_time->value){
        $this->context->addViolation($constraint->message);
    }
    // If the start and end dates are different. We only need to compare dates.
    else if( isset($entity->field_end_date->value) && 
        $entity->field_end_date->value < $entity->field_start_date->value) {
        $this->context->addViolation($constraint->message);
    }
  }
}