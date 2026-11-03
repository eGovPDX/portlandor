<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Dom\HTMLDocument;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SingleAccordionBodyContent constraint.
 */
class SingleAccordionBodyContentConstraintValidator extends ConstraintValidator {
  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint): void {
    if (!isset($field)) return;

    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    $text = $field->value;
    if (empty($text)) return;

    $dom = HTMLDocument::createFromString($text, LIBXML_NOERROR);
    // check for any non-dl tags at the root level
    foreach ($dom->body->childNodes as $child) {
      /** @var \Dom\HTMLElement $child */
      // skip empty p tags from CKEditor
      if ($child->tagName === "P" && mb_trim(str_replace('&nbsp;', '', $child->innerHTML)) === "") {
        continue;
      }

      if ($child->tagName !== 'DL') {
        $this->context->addViolation($constraint->message);
      }
    }

    // check for more than one dl tag at the root level
    if ($dom->body->getElementsByTagName('dl')->length > 1) {
      $this->context->addViolation($constraint->message);
    }
  }
}
