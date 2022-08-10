<?php

namespace Drupal\portland_debt_disclaimer\Plugin\WebformHandler;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Annotation\WebformHandler;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\BrowserKit\Cookie;
/**
 * Set the disclaimer cookie after submission.
 *
 * @WebformHandler(
 *   id = "debt_disclaimer",
 *   label = @Translation("Debt Disclaimer handler"),
 *   category = @Translation("Custom"),
 *   description = @Translation("Set the debt disclaimer cookie after submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class DisclaimerWebformHandler extends WebformHandlerBase {
  /**
   * {@inheritDoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $cookie_name = \Drupal::config("portland_debt_disclaimer.settings")->get("cookie_name");
    $cookie_ttl_hrs = \Drupal::config("portland_debt_disclaimer.settings")->get("cookie_ttl_hrs");

    $value = 1;
    $expire = \Drupal::time()->getRequestTime() + $cookie_ttl_hrs * 60 * 60;
    $path = "/";
    $domain = \Drupal::request()->getHost();
    $secure = true;
    $httponly = true;
    setcookie($cookie_name, $value, $expire, $path, $domain, $secure, $httponly);
  }
}
