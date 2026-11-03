<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Ensures that all of the body content is within the rows of a single accordion.
 * E.g. for enforcing an FAQ field to be a single accordion of questions/answers.
 */
#[Constraint(
  id: 'SingleAccordionBodyContent',
  label: new TranslatableMarkup('Body content in a single accordion', [], ['context' => 'Validation']),
  type: ['text_long'],
)]
class SingleAccordionBodyContentConstraint extends SymfonyConstraint {
  /**
   * The default violation message.
   */
  public string $message = 'All content must be within the rows of a single accordion.';
}
