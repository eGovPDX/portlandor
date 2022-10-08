<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Make sure resource URL has not been added before.
 *
 * @Constraint(
 *   id = "ResourceUrl",
 *   label = @Translation("Make sure resource URL has not been added before.", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class ResourceUrlConstraint extends CompositeConstraintBase {

  /**
   * Message shown when the resource URL already exists.
   *
   * @var string
   */
  public $message = 'Found duplicate resource URL: ';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['field_destination_url'];
  }
}
