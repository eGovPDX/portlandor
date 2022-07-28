<?php

namespace Drupal\portland_debt_disclaimer\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DisclaimerRedirectSubscriber.
 *
 * Contains logic for debt disclaimer redirect
 *
 * We use a cache varying cookie: https://pantheon.io/docs/cookies#cache-varying-cookies
 * That way the page can still be cached for users who have acknowledged the disclaimer,
 * without using a session cookie that would bypass the cache.
 *
 * @package Drupal\portland_debt_disclaimer
 */
class DisclaimerRedirectSubscriber implements EventSubscriberInterface {
  const CRAWLER_USER_AGENTS = [
    "Googlebot",
    "bingbot",
    "facebookexternalhit",
    "Yahoo",
    "Baidu",
    "Sogou",
    "Twitterbot"
  ];

  /**
   * Perform redirect if needed
   */
  public function checkForRedirect(RequestEvent $event) {
    $request = $event->getRequest();
    $path = $request->getPathInfo();
    $path_prefix = \Drupal::config("portland_debt_disclaimer.settings")->get("path_prefix");
    // Prevent redirect loops by checking if prefix is set
    if ($path_prefix !== "/" &&
        $path_prefix !== "" &&
        str_starts_with($path, $path_prefix)) {
      $cookie_name = \Drupal::config("portland_debt_disclaimer.settings")->get("cookie_name");

      $has_acknowledged = $request->cookies->has($cookie_name);
      $is_authenticated = \Drupal::currentUser()->isAuthenticated();
      $is_crawler = $this->isCrawlerUserAgent($request->headers->get("User-Agent"));
      if ($has_acknowledged ||
          $is_authenticated ||
          $is_crawler) return;

      $redirect_to = \Drupal::config("portland_debt_disclaimer.settings")->get("redirect_to");
      $response = new RedirectResponse($redirect_to . "?destination=" . urlencode($path));
      $event->setResponse($response);
    }
  }

  /**
   * Check if the passed user agent is considered a crawler
   */
  private function isCrawlerUserAgent($user_agent) {
    foreach (self::CRAWLER_USER_AGENTS as $crawler_user_agent) {
      if (str_contains($crawler_user_agent, $user_agent)) return true;
    }

    return false;
  }

  /**
   * Get subscribed events.
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => "checkForRedirect",
    ];
  }
}
