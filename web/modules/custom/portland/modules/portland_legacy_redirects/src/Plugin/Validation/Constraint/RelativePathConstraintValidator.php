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
      foreach($field as $delta => $value) {
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

        // test if this path already exists.
        if (!$this->validateUniquePathInForm($path, $delta, $field)) {
          $message = $constraint->duplicate_in_form;
          $this->setViolation($message, $delta);
        }

        // test if this path already exists. this validation function returns either
        // true if valid, or the path to the other duplicate so that it can be included
        // in the error message.
        $is_unique_in_system = $this->validateUniquePathInSystem($path);
        if ($is_unique_in_system !== true) {
          $message = $constraint->duplicate_redirect;
          //$message = "The legacy path already exists in the system (<a href=\"$is_unique_in_system\">$is_unique_in_system</a>). A path cannot redirect to multiple pages.";;
          // NOTE: it might be helpful to show the user the node that contains the duplicate, but they then
          // might try to remove it from that node, which wouldn't delete the existing redirect so that the
          // new one could be recreated. this has the potential to cause confusion.
          $this->setViolation($message, $delta);
        }

      }
    }
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

  function validateUniquePathInForm($path, $delta, $field) {
    // verify that path is unique within all the deltas of the field.
    // if not, both duplicated fields will be highlighted with validation errors.
    foreach ($field as $delta2 => $value) {
      $path2 = $value->value;
      if ($delta == $delta2) continue;
      if ($path == $path2) return false;
    }
    return true;
  }

  function validateUniquePathInSystem($path)
  {
    // search for any nodes that already use this path; might be a node or a group
    $type = 'node';
    $this_node = \Drupal::routeMatch()->getParameter($type);
    if (!$this_node) {
      $type = 'group';
      $this_node = \Drupal::routeMatch()->getParameter($type);
    }
    if (!method_exists($this_node, 'Id')) {
      // if we can't get the id of the node, we can assume the condition is valid
      return true;
    }
    $this_id = $this_node->Id();

    // find all field values from both nodes and groups that match the path being validated
    $matches_node = \Drupal::entityQuery('node')->condition("field_legacy_path", $path)->execute();
    $matches_group = \Drupal::entityQuery('group')->condition("field_legacy_path", $path)->execute();
    $matches = array_merge($matches_node, $matches_group);

    foreach ($matches as $idx => $found_id) {
      if ($found_id == $this_id) continue;
      // we return the path to the duplicate instead of "false" in case we want to display it
      // in a message to the user. only a return value === true signals validity.
      return \Drupal::service('path.alias_manager')->getAliasByPath("/$type/" . $found_id);
    }
    return true;
  }
}