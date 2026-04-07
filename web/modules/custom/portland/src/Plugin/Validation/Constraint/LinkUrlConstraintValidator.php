<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the LinkUrl constraint.
 */
class LinkUrlConstraintValidator extends ConstraintValidator {

  // Define the link fields to validate and their corresponding regex patterns
  const LINK_FIELDS = [
    'field_bluesky' => [
      'type' => 'BlueSky',
      'suggestion' => 'https://bsky.app/profile/',
      'regex' => "/^https:\/\/(www\.)?bsky\.app\/profile\//",
    ],
    'field_linkedin_link' => [
      'type' => 'LinkedIn',
      'suggestion' => 'https://www.linkedin.com/',
      'regex' => "/^https:\/\/(www\.)?linkedin\.com\//",
    ],
    'field_youtube_link' => [
      'type' => 'YouTube',
      'suggestion' => 'https://www.youtube.com/',
      'regex' => "/^https:\/\/(www\.)?youtube\.com\//",
    ],
    'field_nextdoor' => [
      'type' => 'Nextdoor',
      'suggestion' => 'https://nextdoor.com/',
      'regex' => "/^https:\/\/(www\.)?nextdoor\.com\//",
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (!isset($entity)) {
      return;
    }
    
    // Check which link fields are present and need validation
    $validationFields = [];
    foreach (self::LINK_FIELDS as $field_name => $field_info) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $validationFields[$field_name] = $field_info;
      }
    }

    foreach ($validationFields as $field_name => $field_info) {
      // Check if the URL is valid
      $url = $entity->get($field_name)[0]->getValue()['uri'];
      if( $url && !preg_match($field_info['regex'], $url)) {
        $this->context->buildViolation($constraint->message)
          ->atPath($field_name . '.0.uri')
          ->setParameter('%type', $field_info['type'])
          ->setParameter('%suggestion', $field_info['suggestion'])
          ->addViolation();
      }
    }
  }

}
