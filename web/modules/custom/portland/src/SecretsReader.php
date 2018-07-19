<?php

namespace Drupal\portland;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class SecretsReader {
  /**
   * The array of secrets
   *
   * @var array
   */
  private $secrets;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a LoggerChannelFactoryInterface object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory;
    $this->secrets = $this->readSecrets();
  }

  /**
   * Gets a secret from the cached list of secrets
   *
   * @param string $secret_key
   *   The key in the secrets file.
   */
  public function get($secret_key) {
    if (!array_key_exists($secret_key, $this->secrets)) {
      $variables = [
        '@value' => $secret_key,
      ];
      $this->loggerFactory->get('portland')
        ->warning('No secret with key @value found. Returning empty string.', $variables);
      return '';
    }

    return $this->secrets[$secret_key];
  }

  /**
   * Get secrets from secrets file.
   */
  private function readSecrets()
  {
    $secretsFile = $_ENV['HOME'] . '/files/private/secrets.json';
    if (!file_exists($secretsFile)) {
      $variables = [
        '@message' => 'No secrets file found. Returning empty array!',
      ];
      $this->loggerFactory->get('portland')
        ->warning('@message', $variables);
      return [];
    }
    $secretsContents = file_get_contents($secretsFile);
    $secrets = json_decode($secretsContents, 1);
    if ($secrets == FALSE) {
      $variables = [
        '@message' => 'Could not parse json in secrets file. Returning empty array!',
      ];
      $this->loggerFactory->get('portland')
        ->warning('@message', $variables);
      return [];
    }

    return $secrets;
  }
}
