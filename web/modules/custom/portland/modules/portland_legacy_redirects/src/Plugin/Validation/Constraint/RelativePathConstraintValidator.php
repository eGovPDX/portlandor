<?php

namespace Drupal\portland_legacy_redirects\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Validates the PreventAnon constraint.
 */
class RelativePathConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint) {

    // is it single value or FieldItemList?
    if ($field instanceof FieldItemListInterface) {
      foreach( $field as $delta => $value) {
        $path = $value->value;

        // test if path is bare (no protocol)
        if (!$this->validateRelative($path)) {
          // remove protocol from path for use as a suggestion in the validation message
          $relative_path = parse_url($path, PHP_URL_PATH);
          $message = $constraint->not_relative . " Try using \"$relative_path\" instead.";
          $this->setViolation($message, $delta);
        }

        // test if path starts with slash
        if (!$this->validateStartsWithSlash($path)) {
          $message = $constraint->not_relative;
          $this->setViolation($message, $delta);
        }

        // test if any illegal characters
        if (!$this->validateValidChars($path)) {
          $message = $constraint->illegal_chars;
          $this->setViolation($message, $delta);
        }

      }
    }
    
    $test = "stop";
  }

  // creates and adds a violation, but also sets the property path so that the
  // correct field in the multiple-value widget is targeted.
  function setViolation($message, $delta) {
    $this->context->buildViolation($message)
      ->atPath((string) $delta)
      ->addViolation();
  }

  function validateRelative($path) {
    // don't want to see http/https at start of path.
    return !preg_match('/^(?:http|https):\/\/.+$/', $path);
  }

  function validateStartsWithSlash($path) {
    // want to see slash at start of path
    return preg_match('/^\/.+/', $path);
  }

  function validateValidChars($path) {
    // only want to see these characters in path
    return !preg_match('/[^\/\w.-]/', $path);
  }
}