<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PreventAnon constraint.
 */
class ResourceUrlConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity) || $entity->bundle() != 'external_resource') {
      return;
    }

    // Check if any resource has the same value in field_destination_url
    $resources = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'field_destination_url' => $entity->field_destination_url[0]->getUrl()->getUri(),
    ]);

    // Found duplicates
    if( count($resources) > 0) {
      $resource_urls = [];
      foreach(array_values($resources) as $resource) {
        $resource_urls []= "<a href=\"" . $resource->toUrl()->toString() . "\">" . $resource->getTitle() . "</a>";
      }
      $this->context->addViolation($constraint->message . join(", ", $resource_urls));
    }
  }
}
