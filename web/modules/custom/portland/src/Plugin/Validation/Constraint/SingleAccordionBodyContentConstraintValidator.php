<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Dom\HTMLDocument;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SingleAccordionBodyContent constraint.
 */
class SingleAccordionBodyContentConstraintValidator extends ConstraintValidator {
  private function addViolation(int $delta, Constraint $constraint): void {
    $this->context->buildViolation($constraint->message)
      ->atPath((string) $delta)
      ->addViolation();
  }

  /**
   * {@inheritdoc}
   */
  public function validate($field, Constraint $constraint): void {
    if (!isset($field)) return;

    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    foreach ($field as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $text = $item->value;
      if (empty($text)) continue;

      $dom = HTMLDocument::createFromString($text, LIBXML_NOERROR);
      // check for more than one dl tag at the root level
      if ($dom->body->getElementsByTagName('dl')->length > 1) {
        $this->addViolation($delta, $constraint);
        return;
      }

      // check for any non-dl tags at the root level
      foreach ($dom->body->childNodes as $child) {
        /** @var \Dom\HTMLElement $child */
        if ($child->tagName !== 'DL') {
          $this->addViolation($delta, $constraint);
          return;
        }
      }
    }
  }
}
