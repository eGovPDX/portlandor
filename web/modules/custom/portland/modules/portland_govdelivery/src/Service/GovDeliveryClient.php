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
  public function getCredentials(): array
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
  public function getAccountApiBase(): string
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
   * 
   * @param string $email
   *   Subscriber email address.
   * @param array|string $topics
   *   Topic code(s) to subscribe to.
   * @param string|null $locale
   *   Optional locale code.
   * @param array $answers
   *   Optional array of question answers: ['QUESTION_CODE' => 'answer value']
   */
  public function subscribeUser(string $email, $topics = [], ?string $locale = NULL, array $answers = []): array
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
    
    // NOTE: Custom question responses are NOT supported via subscriptions.xml.
    // We'll update responses after a successful subscription using the
    // Subscribers Responses endpoint.
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

        // If there are answers, update the subscriber's question responses now.
        if (!empty($answers)) {
          try {
            $updated = $this->updateSubscriberResponses($email, $answers);
            if ($updated) {
              $this->logger->info('Updated question responses for %email after subscription.', ['%email' => $email]);
            } else {
              $this->logger->warning('Attempted to update question responses for %email, but no matching questions were found or payload was empty.', ['%email' => $email]);
            }
          } catch (\Throwable $e) {
            $this->logger->error('Failed to update question responses for %email: @msg', ['%email' => $email, '@msg' => $e->getMessage()]);
          }
        }
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
   * Update a subscriber's question responses via PUT /subscribers/{encoded}/responses.xml.
   *
   * @param string $email
   *   Subscriber email address.
   * @param array $answers
   *   Array of question-code => answer value(s). Values may be string or array.
   *
   * @return bool
   *   TRUE if a request was sent (non-empty payload), FALSE otherwise.
   */
  public function updateSubscriberResponses(string $email, array $answers): bool
  {
    // Normalize answers: drop empties; coerce arrays and strings.
    $normalized = [];
    foreach ($answers as $code => $value) {
      if (is_array($value)) {
        $vals = array_filter(array_map('trim', $value), static fn($v) => $v !== '' && $v !== NULL);
      } else {
        // Split common delimiters if handler provided a single string value with delimiters.
        $parts = preg_split('/[\r\n,|]+/', (string) $value);
        $vals = array_filter(array_map('trim', $parts), static fn($v) => $v !== '' && $v !== NULL);
      }
      if (!empty($vals)) {
        $normalized[(string) $code] = array_values($vals);
      }
    }

    if (empty($normalized)) {
      return FALSE;
    }

    [$username, $password] = $this->getCredentials();
    $endpoint = $this->getAccountApiBase();
    $encodedSubscriber = base64_encode($email);

    // Build a lookup map of the subscriber's questions: name => details.
    $questionMap = $this->fetchSubscriberQuestionMap($encodedSubscriber, $username, $password, $endpoint);
    if (empty($questionMap)) {
      $this->logger->warning('No subscriber questions returned for %email; cannot map responses.', ['%email' => $email]);
      return FALSE;
    }

    // Construct responses payload.
    $responses = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><responses/>');
    $responses->addAttribute('type', 'array');

    $added = 0;
    foreach ($normalized as $code => $values) {
      $lookupKey = strtolower(trim((string) $code));
      if (!isset($questionMap[$lookupKey])) {
        $this->logger->warning('Question code "%code" not found among subscriber questions for %email.', ['%email' => $email, '%code' => $code]);
        continue;
      }
      $qInfo = $questionMap[$lookupKey];
      $qType = $qInfo['type'];
      $qId = $qInfo['id']; // Base64-encoded question ID (to-param).
      $answerLookup = $qInfo['answers']; // map lower(answer-text) => encoded answer id.

      foreach ($values as $v) {
        $vStr = (string) $v;
        if ($vStr === '') { continue; }
        $resp = $responses->addChild('response');
        $resp->addChild('question-answer-text', htmlspecialchars($vStr, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
        $resp->addChild('question-id', $qId);

        // If it's a select question and we can map the text to an answer-id, include it.
        $answerId = NULL;
        if (strpos($qType, 'select_') === 0 || $qType === 'select_one' || $qType === 'select_multi') {
          $key = strtolower($vStr);
          if (isset($answerLookup[$key])) {
            $answerId = $answerLookup[$key];
          }
        }

        if ($answerId) {
          $resp->addChild('answer-id', $answerId);
        } else {
          $answerNode = $resp->addChild('answer-id');
          $answerNode->addAttribute('nil', 'true');
        }
        $added++;
      }
    }

    if ($added === 0) {
      return FALSE;
    }

    $payload = $responses->asXML();
    // Log a small snippet for debugging; avoid angle-bracket stripping by encoding.
    $this->logger->debug('GovDelivery responses PUT payload (base64-xml): @payload', [
      '@payload' => base64_encode(substr($payload, 0, 3000)),
    ]);

    try {
      $res = $this->httpClient->request('PUT', $endpoint . '/subscribers/' . rawurlencode($encodedSubscriber) . '/responses.xml', [
        'auth' => [$username, $password],
        'headers' => [
          'Accept' => 'application/xml',
          'Content-Type' => 'application/xml',
        ],
        'body' => $payload,
        'http_errors' => FALSE,
        'timeout' => 20,
      ]);
      $status = (int) $res->getStatusCode();
      if ($status >= 200 && $status < 300) {
        return TRUE;
      }
      $this->logger->error('GovDelivery responses PUT failed for %email: HTTP @status @reason @snippet', [
        '%email' => $email,
        '@status' => $status,
        '@reason' => $res->getReasonPhrase(),
        '@snippet' => substr((string) $res->getBody(), 0, 300),
      ]);
      return FALSE;
    } catch (\Throwable $e) {
      $this->logger->error('GovDelivery responses PUT exception for %email: @msg', ['%email' => $email, '@msg' => $e->getMessage()]);
      throw $e;
    }
  }

  /**
   * Build a map of subscriber questions for quick lookup by name (lowercased).
   *
   * @return array
   *   [ lower(name) => [ 'id' => base64_question_id, 'type' => response-type, 'answers' => [lower(answer-text) => base64_answer_id] ] ]
   */
  protected function fetchSubscriberQuestionMap(string $encodedSubscriber, string $username, string $password, string $endpoint): array
  {
    try {
      $attempts = 0;
      do {
        $attempts++;
        // First, list questions for this subscriber (links only).
        $qListRes = $this->httpClient->request('GET', $endpoint . '/subscribers/' . rawurlencode($encodedSubscriber) . '/questions.xml', [
          'auth' => [$username, $password],
          'headers' => ['Accept' => 'application/xml'],
          'http_errors' => FALSE,
          'timeout' => 15,
        ]);
        if ((int) $qListRes->getStatusCode() !== 200) {
          $this->logger->warning('GovDelivery questions list HTTP @status for subscriber @sid', [
            '@status' => (int) $qListRes->getStatusCode(),
            '@sid' => $encodedSubscriber,
          ]);
          return [];
        }
        $qListXml = @simplexml_load_string((string) $qListRes->getBody());
        if ($qListXml === FALSE) {
          return [];
        }
        $links = $qListXml->xpath('//*[local-name()="link" and @rel="self" and contains(@href, "/questions/")]');
        if (!empty($links)) {
          // Build the map now that we have links.
          $map = [];
          foreach ($links as $lnk) {
            $href = (string) $lnk['href'];
            if ($href === '') { continue; }
            // Build absolute URL if necessary.
            $url = (strpos($href, 'http') === 0) ? $href : rtrim($endpoint, '/') . $href;
            // Fetch each question detail.
            $qRes = $this->httpClient->request('GET', $url, [
              'auth' => [$username, $password],
              'headers' => ['Accept' => 'application/xml'],
              'http_errors' => FALSE,
              'timeout' => 15,
            ]);
            if ((int) $qRes->getStatusCode() !== 200) { continue; }
            $qXml = @simplexml_load_string((string) $qRes->getBody());
            if ($qXml === FALSE) { continue; }

            $nameNode = $qXml->xpath('//*[local-name()="name"]');
            $typeNode = $qXml->xpath('//*[local-name()="response-type"]');
            $idNode = $qXml->xpath('//*[local-name()="to-param"]');
            $name = !empty($nameNode[0]) ? (string) $nameNode[0] : '';
            $type = !empty($typeNode[0]) ? (string) $typeNode[0] : '';
            $qid  = !empty($idNode[0]) ? (string) $idNode[0] : '';
            if ($name === '' || $qid === '') { continue; }

            // Collect answer choices if present (for select questions).
            $answers = [];
            $answerNodes = $qXml->xpath('//*[local-name()="answers"]/*[local-name()="answer"]');
            if (!empty($answerNodes)) {
              foreach ($answerNodes as $ans) {
                $txtNode = $ans->xpath('./*[local-name()="answer-text"]');
                $aidNode = $ans->xpath('./*[local-name()="to-param"]');
                $aText = !empty($txtNode[0]) ? (string) $txtNode[0] : '';
                $aId   = !empty($aidNode[0]) ? (string) $aidNode[0] : '';
                if ($aText !== '' && $aId !== '') {
                  $answers[strtolower($aText)] = $aId;
                }
              }
            }

            $map[strtolower($name)] = [
              'id' => $qid,
              'type' => $type,
              'answers' => $answers,
            ];
          }
          return $map;
        }

        // If first attempt yields no links, wait briefly and retry (eventual consistency after subscription).
        if ($attempts < 3) {
          usleep(500000); // 0.5s
        }
      } while ($attempts < 3);

      return [];
    } catch (\Throwable $e) {
      $this->logger->error('Failed to fetch subscriber questions: @msg', ['@msg' => $e->getMessage()]);
      return [];
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

  /**
   * Fetch all account-level questions via JSON endpoint.
   *
   * Endpoint: GET {account_base}/questions.json
   * Returns a normalized array:
   * [
   *   [ 'id' => '123', 'name' => 'Preferred language', 'topic_codes' => ['TOPIC_A','TOPIC_B'] ],
   *   ...
   * ]
   *
   * Any additional fields present in the JSON will be retained under 'raw'.
   */
}
