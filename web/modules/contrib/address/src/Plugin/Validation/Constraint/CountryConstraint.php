<?php

namespace Drupal\address\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Country constraint.
 *
 * @Constraint(
 *   id = "Country",
 *   label = @Translation("Country", context = "Validation"),
 * )
 */
class CountryConstraint extends Constraint {

  public $availableCountries = [];
  public $invalidMessage = 'The country %value is not valid.';
  public $notAvailableMessage = 'The country %value is not available.';

}
