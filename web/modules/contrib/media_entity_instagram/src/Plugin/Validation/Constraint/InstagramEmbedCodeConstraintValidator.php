<?php

namespace Drupal\media_entity_instagram\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\media_entity_instagram\Plugin\media\Source\Instagram;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the InstagramEmbedCode constraint.
 */
class InstagramEmbedCodeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $data = '';
    if (is_string($value)) {
      $data = $value;
    }
    elseif ($value instanceof FieldItemInterface) {
      $class = get_class($value);
      $property = $class::mainPropertyName();
      if ($property) {
        $data = $value->{$property};
      }
    }

    if ($data) {
      $matches = [];
      foreach (Instagram::$validationRegexp as $pattern => $key) {
        if (preg_match($pattern, $data, $item_matches)) {
          $matches[] = $item_matches;
        }
      }
      if (empty($matches)) {
        $this->context->addViolation($constraint->message);
      }
    }

  }

}
