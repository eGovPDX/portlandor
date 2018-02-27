<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jsonapi\Exception\UnprocessableHttpEntityException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Normalizes an UnprocessableHttpEntityException object for JSON output which
 * complies with the JSON API specification. A source pointer is added to help
 * client applications report validation errors, for example on an Entity edit
 * form.
 *
 * @see http://jsonapi.org/format/#error-objects
 */
class UnprocessableHttpEntityExceptionNormalizer extends HttpExceptionNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = UnprocessableHttpEntityException::class;

  /**
   * UnprocessableHttpEntityException constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(AccountProxyInterface $current_user) {
    parent::__construct($current_user);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildErrorObjects(HttpException $exception) {
    /** @var $exception \Drupal\jsonapi\Exception\UnprocessableHttpEntityException */
    $errors = parent::buildErrorObjects($exception);
    $error = $errors[0];
    unset($error['links']);

    $errors = [];
    $violations = $exception->getViolations();
    $entity_violations = $violations->getEntityViolations();
    foreach ($entity_violations as $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
      $error['detail'] = 'Entity is not valid: '
        . $violation->getMessage();
      $error['source']['pointer'] = '/data';
      $errors[] = $error;
    }

    $entity = $violations->getEntity();
    foreach ($violations->getFieldNames() as $field_name) {
      $field_violations = $violations->getByField($field_name);
      $cardinality = $entity->get($field_name)
        ->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getCardinality();

      foreach ($field_violations as $violation) {
        /** @var \Symfony\Component\Validator\ConstraintViolation $violation */
        $error['detail'] = $violation->getPropertyPath() . ': '
          . $violation->getMessage();

        $pointer = '/data/attributes/'
          . str_replace('.', '/', $violation->getPropertyPath());
        if ($cardinality == 1) {
          // Remove erroneous '/0/' index for single-value fields.
          $pointer = str_replace("/data/attributes/$field_name/0/", "/data/attributes/$field_name/", $pointer);
        }
        $error['source']['pointer'] = $pointer;

        $errors[] = $error;
      }
    }

    return $errors;
  }

}
