<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Validates NewsDateTimeConstraint.
 */
class NewsDateTimeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity->bundle() != 'news') {
      return;
    }

    if( $entity->hasField('field_updated_on') && $entity->hasField('field_published_on') ) {
      $updated_on = new DrupalDateTime($entity->field_updated_on->value);
      $published_on = new DrupalDateTime($entity->field_published_on->value);
      if($updated_on->getTimestamp() < $published_on->getTimestamp()) {
        $this->context->buildViolation($constraint->errorMessage)
          ->atPath('field_updated_on.0.value')
          ->addViolation();
      }
    }
  }
}
