<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ImageSliderImagesRequired constraint.
 */
class ImageSliderImagesRequiredConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity->bundle() !== 'image_slider') {
      return;
    }

    if ($entity->get('image')->count() < 2) {
      $this->context->buildViolation($constraint->message)
        ->atPath('image')
        ->addViolation();
    }
  }

}
