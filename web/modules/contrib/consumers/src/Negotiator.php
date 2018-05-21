<?php

namespace Drupal\consumers;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extracts the consumer information from the given context.
 */
class Negotiator {

  /**
   * Protected requestStack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Protected entityRepository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Instantiates a new Negotiator object.
   */
  public function __construct(RequestStack $request_stack, EntityRepositoryInterface $entity_repository) {
    $this->requestStack = $request_stack;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Obtains the consumer from the request.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request. NULL to use the current request.
   *
   * @return \Drupal\consumers\Entity\Consumer|null
   *   The consumer.
   */
  public function negotiateFromRequest(Request $request = NULL) {
    // If the request is not provided, use the request from the stack.
    $request = $request ? $request : $this->requestStack->getCurrentRequest();
    // There are several ways to negotiate the consumer:
    // 1. Via a custom header.
    $consumer_uuid = $request->headers->get('X-Consumer-ID');
    if (!$consumer_uuid) {
      // 2. Via a query string parameter.
      $consumer_uuid = $request->query->get('_consumer_id');
    }
    if (!$consumer_uuid) {
      return NULL;
    }
    /** @var \Drupal\consumers\Entity\Consumer $consumer */
    $consumer = $this->entityRepository->loadEntityByUuid('consumer', $consumer_uuid);
    return $consumer;
  }

}
