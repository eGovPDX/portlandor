<?php

declare(strict_types=1);

namespace Drupal\portland\Logger\ConditionResolver;

use Drupal\monolog\Logger\ConditionResolver\ConditionResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Condition resolver that checks if the hostname is in a whitelist.
 */
class HostnameResolver implements ConditionResolverInterface
{

  /**
   * HostnameResolver constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param string[] $whitelistHosts
   *   Array of hostnames that should trigger the "both" handler condition.
   *   Configurable via the 'arguments' key in the services yml file. 
   *   See monolog.services.yml
   */
  public function __construct(
    private readonly RequestStack $requestStack,
    private readonly array $whitelistHosts = ['www.portland.gov', 'employees.portland.gov'],
  ) {}
  /**
   * {@inheritdoc}
   */
  public function resolve(): bool
  {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return in_array($request->getHost(), $this->whitelistHosts);
    }
    return false;
  }
}
