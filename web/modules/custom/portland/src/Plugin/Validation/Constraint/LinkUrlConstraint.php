<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Make sure link URL is a valid URL.
 *
 * @Constraint(
 *   id = "LinkUrl",
 *   label = @Translation("Make sure link URL is a valid URL.", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class LinkUrlConstraint extends Constraint {

  /**
   * Message shown when the URL is invalid.
   *
   * @var string
   */
  public $message = 'Please enter a valid %type URL that starts with %suggestion';

}
