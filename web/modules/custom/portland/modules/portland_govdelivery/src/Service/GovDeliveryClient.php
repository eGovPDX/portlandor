<?php

namespace Drupal\portland_govdelivery\Service;

use GuzzleHttp\ClientInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * GovDelivery client tailored for Portland.
 */
class GovDeliveryClient
{
  protected ClientInterface $httpClient;
  protected KeyRepositoryInterface $keyRepository;
  protected ConfigFactoryInterface $configFactory;
  protected $logger;

  public function __construct(ClientInterface $http_client, KeyRepositoryInterface $key_repository, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory)
  {
    $this->httpClient = $http_client;
    $this->keyRepository = $key_repository;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('portland_govdelivery');
  }

  

  /**
   * Get credentials using key IDs configured in portland_govdelivery.settings.
   * Returns [username, password].
   */
  protected function getCredentials(): array
  {
    $config = $this->configFactory->get('portland_govdelivery.settings');
    $username_key_id = $config->get('username_key') ?: 'govdelivery_username';
    $password_key_id = $config->get('password_key') ?: 'govdelivery_password';

    $username_key = $this->keyRepository->getKey($username_key_id);
    $password_key = $this->keyRepository->getKey($password_key_id);

    if (!$username_key || !$password_key) {
      throw new \RuntimeException('GovDelivery keys not found. Please configure username and password keys in Portland GovDelivery settings.');
    }

    $username = $username_key->getKeyValue();
    $password = $password_key->getKeyValue();
    return [$username, $password];
  }

  /**
   * Determine the API endpoint.
   */
  protected function getAccountApiBase(): string
  {
    // Only use api_base_url + account_code from portland_govdelivery.settings
    $config = $this->configFactory->get('portland_govdelivery.settings');
    $api_base = $config->get('api_base_url');
    $account_code = $config->get('account_code');

    if (empty($api_base) || empty($account_code)) {
      $error_msg = 'GovDelivery API base URL and account code must be configured in Portland GovDelivery settings.';
      $this->logger->error($error_msg);
      throw new \RuntimeException($error_msg);
    }

    return rtrim($api_base, '/') . '/' . $account_code;
  }

  /**
   * Subscribe a single user immediately. Topics can be an array or single string.
   * 
   * Uses the Subscriptions resource (POST /subscriptions.xml) which:
   * - Creates subscriber if they don't exist
   * - Adds topics incrementally (doesn't remove existing ones)
   * - Avoids 422 duplicate errors
   */
  public function subscribeUser(string $email, $topics = [], ?string $locale = NULL): array
  {
    [$username, $password] = $this->getCredentials();
    $endpoint = $this->getAccountApiBase();

    if (is_string($topics)) {
      $topics = [$topics];
    }
    $topics = array_values(array_filter((array) $topics));

    // Build XML payload for Subscriptions resource.
    // This creates the subscriber if missing, or adds topics if they exist.
    $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subscriber/>');
    $xml->addChild('email', htmlspecialchars($email, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
    
    // Disable notification emails when adding topics.
    $sendNotification = $xml->addChild('send-notification', 'false');
    $sendNotification->addAttribute('type', 'boolean');
    
    if (!empty($topics)) {
      $topicsNode = $xml->addChild('topics');
      $topicsNode->addAttribute('type', 'array');
      foreach ($topics as $code) {
        $topicNode = $topicsNode->addChild('topic');
        $topicNode->addChild('code', htmlspecialchars((string) $code, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
      }
    }
    
    if (!empty($locale)) {
      $xml->addChild('locale', htmlspecialchars($locale, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
    }
    
    $payload = $xml->asXML();

    try {
      // Use the Subscriptions resource - handles both new and existing subscribers.
      $response = $this->httpClient->request('POST', $endpoint . '/subscriptions.xml', [
        'auth' => [$username, $password],
        'headers' => [
          'Accept' => 'application/xml',
          'Content-Type' => 'application/xml',
        ],
        'body' => $payload,
        'http_errors' => FALSE,
        'timeout' => 15,
      ]);
      $status = (int) $response->getStatusCode();
      if ($status >= 200 && $status < 300) {
        $respBody = (string) $response->getBody();
        $out = ['status' => $status];
        if ($respBody !== '') {
          $respXml = @simplexml_load_string($respBody);
          if ($respXml !== FALSE) {
            $emailNode = $respXml->xpath('//*[local-name()="email"]');
            if (!empty($emailNode[0])) {
              $out['email'] = (string) $emailNode[0];
            }
          }
        }
        $this->logger->info('Successfully subscribed %email to topics via Subscriptions resource.', ['%email' => $email]);
        return $out;
      }

      $reason = $response->getReasonPhrase();
      $body_snippet = substr((string) $response->getBody(), 0, 500);
      $msg = "GovDelivery subscriptions endpoint returned HTTP $status $reason: $body_snippet";
      $this->logger->error('GovDelivery subscribe failed for %email: @msg', ['%email' => $email, '@msg' => $msg]);
      throw new \RuntimeException($msg);
    } catch (\Throwable $e) {
      $this->logger->error('GovDelivery subscribe exception for %email: @msg', ['%email' => $email, '@msg' => $e->getMessage()]);
      throw $e;
    }
  }

  /**
   * Retrieve topics (XML endpoint) and return a normalized array.
   */
  public function getTopics(): array
  {
    $endpoint = $this->getAccountApiBase();
    $options = [
      'timeout' => 15,
      'headers' => [
        'Accept' => 'application/xml',
      ],
      'http_errors' => FALSE,
    ];
    // If credentials are available, include them; otherwise attempt unauthenticated.
    try {
      [$username, $password] = $this->getCredentials();
      if ($username !== '' || $password !== '') {
        $options['auth'] = [$username, $password];
      }
    } catch (\Throwable $e) {
      // No credentials available; proceed without auth.
    }

    try {
      $url = $endpoint . '/topics';
      $response = $this->httpClient->request('GET', $url, $options);
      $status = (int) $response->getStatusCode();
      if ($status !== 200) {
        $this->logger->warning('GovDelivery getTopics HTTP @status for @url', [
          '@status' => $status,
          '@url' => $url,
        ]);
        return [];
      }

      $xml_string = (string) $response->getBody();
      if ($xml_string === '') {
        return [];
      }

      $xml = @simplexml_load_string($xml_string);
      if ($xml === FALSE) {
        $this->logger->error('GovDelivery getTopics: failed to parse XML response.');
        return [];
      }

      // Use namespace-agnostic XPath queries.
      $nodes = $xml->xpath('//*[local-name()="topic"]') ?: [];
      $topics = [];
      foreach ($nodes as $node) {
        $codeNode = $node->xpath('./*[local-name()="code"]');
        $nameNode = $node->xpath('./*[local-name()="name"]');
        if (empty($nameNode)) {
          $nameNode = $node->xpath('./*[local-name()="title"]');
        }
        $descNode = $node->xpath('./*[local-name()="short_description"]');
        if (empty($descNode)) {
          $descNode = $node->xpath('./*[local-name()="description"]');
        }

        $code = isset($codeNode[0]) ? (string) $codeNode[0] : '';
        $name = isset($nameNode[0]) ? (string) $nameNode[0] : '';
        $desc = isset($descNode[0]) ? (string) $descNode[0] : '';

        if ($code === '' && $name === '') {
          continue;
        }

        $topics[] = [
          'code' => $code,
          'name' => $name,
          'description' => $desc,
        ];
      }

      return $topics;
    } catch (\Throwable $e) {
      $this->logger->error('GovDelivery getTopics failed: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Enqueue a subscribe request.
   */
  public function queueSubscribe(array $item): bool
  {
    $queue = \Drupal::queue('govdelivery_subscribe');
    $queue->createItem($item);
    return TRUE;
  }
}
