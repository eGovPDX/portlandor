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
    if ($field instanceof FieldItemListInterface && $field->getName() == "field_redirects") {
      foreach($field as $delta => $value) {
        $path = $value->value;

        $entity = $field->getEntity();
        $original = $entity->original;

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
        // in the error message. if validation is working, there should only ever be
        // one value returned in the array on failure.
        //$group_id = $entity->get('field_group')->getValue()[0]['target_id'];
        $eid = $entity->Id();
        $type = $entity->getEntityTypeId();

        $is_unique_in_system = $this->validateUniquePathInSystem($path, $type, $eid);
        if ($is_unique_in_system !== true && !is_null($is_unique_in_system)) {
          $keys = array_keys($is_unique_in_system);
          $duplicate = $is_unique_in_system[$keys[0]];
          $dup_path = "/" . $duplicate->get('redirect_source')->getValue()[0]['path'];
          
          $path_link_message = "";
          if (is_array($is_unique_in_system) && count($is_unique_in_system) > 0) {
            $path_link_message = " (<a href=\"$dup_path\" target=\"_blank\">$dup_path</a>)";
          }
          $message = "The legacy path already exists in the system$path_link_message. A path cannot redirect to multiple pages.";;
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
    $path_without_trailing_slash = rtrim($path, "/");
    foreach ($field as $delta2 => $value) {
      $path2 = $value->value;
      if ($delta == $delta2) continue;
      if ($path_without_trailing_slash == $path2) return false;
    }
    return true;
  }

  function validateUniquePathInSystem($path, $type, $eid)
  {
    // all: valid if no source path matches found.
    // new page (no $eid): invalid if any source path matches found.
    // existing page: invalid if source path and redirect uri don't match (valid if both match).

    // strip leading slash from path
    $path = substr($path, 0, 1) == "/" ? substr($path, 1) : $path;

    // there are 2 possible URI formats the redirect might use; need to test for both
    $uri1 = "entity:$type/$eid";
    $uri2 = "internal:/$type/$eid";

    // get redirects with matching source
    $matches = \Drupal::service('redirect.repository')->findBySourcePath($path);
    $invalid = [];

    // return true immediately if no matches found; yay, valid!
    if (!$matches || count($matches) < 1) {
      return TRUE;
    }

    // spin through matches...
    // if $eid is null (new page), then any matches are invalid
    if (!$eid && count($matches)) {
      return $matches;
    }

    // $eid is not null; if redirect uri matches current, it's valid
    foreach($matches as $key => $redirect) {
      $existing_uri = $redirect->get('redirect_redirect')->getValue()[0]['uri'];
      if ($existing_uri != $uri1 && $existing_uri != $uri2) {
        $invalid[] = $redirect;
      }
    }
    return count($invalid) > 0 ? $invalid : TRUE;
  }
}