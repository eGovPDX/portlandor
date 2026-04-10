<?php

namespace Drupal\portland_govdelivery\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service to fetch and cache GovDelivery questions.
 */
class QuestionsProvider {

  protected GovDeliveryClient $client;
  protected CacheBackendInterface $cache;
  protected $logger;

  const CACHE_ID = 'portland_govdelivery:questions';
  const CACHE_TAG = 'portland_govdelivery_questions';
  const CACHE_TTL = 3600; // 1 hour

  public function __construct(GovDeliveryClient $client, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory) {
    $this->client = $client;
    $this->cache = $cache;
    $this->logger = $logger_factory->get('portland_govdelivery');
  }

  /**
   * Get all questions from GovDelivery.
   *
   * Results are cached for performance.
   *
   * @param bool $reset
   *   If TRUE, bypass cache and fetch fresh from API.
   *
   * @return array
   *   Array of questions, keyed by question ID (to-param).
   *   Each question contains: id, name, question_text, response_type, topics, answers.
   */
  public function getAllQuestions(bool $reset = FALSE): array {
    if (!$reset) {
      $cached = $this->cache->get(self::CACHE_ID);
      if ($cached !== FALSE) {
        return $cached->data;
      }
    }

    $questions = $this->fetchAllQuestionsFromApi();
    
    // Cache with TTL and custom tag.
    $this->cache->set(
      self::CACHE_ID,
      $questions,
      time() + self::CACHE_TTL,
      [self::CACHE_TAG]
    );

    return $questions;
  }

  /**
   * Get the UNIX timestamp when the questions cache item was last written.
   *
   * @return int|null
   *   The created timestamp, or NULL if there is no cache item.
   */
  public function getLastFetched(): ?int {
    $cached = $this->cache->get(self::CACHE_ID);
    if ($cached === FALSE) {
      return NULL;
    }
    return isset($cached->created) ? (int) $cached->created : NULL;
  }

  /**
   * Fetch all public account-level and topic-specific questions.
   */
  protected function fetchAllQuestionsFromApi(): array {
    try {
      $account_base = $this->client->getAccountApiBase();
      [$username, $password] = $this->client->getCredentials();
      
      // 1. Fetch account-level public questions.
      $questions = $this->fetchAccountLevelQuestions($account_base, $username, $password);
      
      // 2. Fetch all topics.
      /** @var \Drupal\portland_govdelivery\Service\TopicsProvider $tp */
      $tp = \Drupal::service('portland_govdelivery.topics_provider');
      $topics = $tp->getAllTopics();
      
      // 3. Iterate topics and fetch their questions.
      foreach ($topics as $topic) {
        $code = $topic['code'] ?? NULL;
        if (!$code) { continue; }
        $topicQuestions = $this->fetchTopicQuestions($code, $account_base, $username, $password);
        foreach ($topicQuestions as $qid => $q) {
          if (isset($questions[$qid])) {
            // Merge topic association into existing question.
            if (!in_array($code, $questions[$qid]['topics'])) {
              $questions[$qid]['topics'][] = $code;
            }
          }
          else {
            // New question discovered via topic.
            $q['topics'][] = $code;
            $questions[$qid] = $q;
          }
        }
      }
      
      if (empty($questions)) {
        $this->logger->warning('No questions found after fetching account-level and topic questions.');
      }
      return $questions;
    } catch (\Throwable $e) {
      $this->logger->error('Error fetching questions: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Fetch account-level public questions from /questions.xml.
   */
  protected function fetchAccountLevelQuestions(string $account_base, string $username, string $password): array {
    $url = rtrim($account_base, '/') . '/questions.xml';
    $response = \Drupal::httpClient()->request('GET', $url, [
      'auth' => [$username, $password],
      'headers' => ['Accept' => 'application/xml'],
      'http_errors' => FALSE,
      'timeout' => 15,
    ]);
    if ((int) $response->getStatusCode() !== 200) {
      return [];
    }
    $xml = @simplexml_load_string((string) $response->getBody());
    if ($xml === FALSE) {
      return [];
    }
    return $this->extractQuestionsFromListingXml($xml, $account_base, $username, $password);
  }

  /**
   * Fetch questions for a specific topic from /topics/{CODE}/questions.xml.
   */
  protected function fetchTopicQuestions(string $topic_code, string $account_base, string $username, string $password): array {
    $url = rtrim($account_base, '/') . '/topics/' . rawurlencode($topic_code) . '/questions.xml';
    $response = \Drupal::httpClient()->request('GET', $url, [
      'auth' => [$username, $password],
      'headers' => ['Accept' => 'application/xml'],
      'http_errors' => FALSE,
      'timeout' => 15,
    ]);
    if ((int) $response->getStatusCode() !== 200) {
      return [];
    }
    $xml = @simplexml_load_string((string) $response->getBody());
    if ($xml === FALSE) {
      return [];
    }
    return $this->extractQuestionsFromListingXml($xml, $account_base, $username, $password);
  }

  /**
   * Extract questions from a listing XML (account or topic).
   */
  protected function extractQuestionsFromListingXml(\SimpleXMLElement $xml, string $account_base, string $username, string $password): array {
    // Prefer <question-uri> elements which include the .xml detail URL.
    $uri_nodes = $xml->xpath('//*[local-name()="question-uri"]');
    if (empty($uri_nodes)) {
      // Fallback to link hrefs if question-uri missing.
      $uri_nodes = $xml->xpath('//*[local-name()="link" and contains(@href, "/questions/")]');
    }
    if (empty($uri_nodes)) {
      return [];
    }
    $questions = [];
    foreach ($uri_nodes as $node) {
      $raw = trim((string) $node);
      if ($raw === '') { continue; }
      // Build absolute detail URL.
      if (strpos($raw, 'http') === 0) {
        $qUrl = $raw;
      }
      elseif (strpos($raw, '/') === 0) {
        $parts = parse_url($account_base);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? parse_url($account_base, PHP_URL_HOST);
        $qUrl = $scheme . '://' . $host . $raw;
      }
      else {
        $qUrl = rtrim($account_base, '/') . '/' . ltrim($raw, '/');
      }
      // Ensure .xml suffix for detail if missing.
      if (!str_ends_with($qUrl, '.xml')) {
        $qUrl .= '.xml';
      }
      $qRes = \Drupal::httpClient()->request('GET', $qUrl, [
        'auth' => [$username, $password],
        'headers' => ['Accept' => 'application/xml'],
        'http_errors' => FALSE,
        'timeout' => 15,
      ]);
      $status = (int) $qRes->getStatusCode();
      if ($status !== 200) {
        $this->logger->warning('Question detail fetch failed @status for @url', ['@status' => $status, '@url' => $qUrl]);
        continue;
      }
      $qXml = @simplexml_load_string((string) $qRes->getBody());
      if ($qXml === FALSE) { continue; }
      $parsed = $this->parseQuestionXml($qXml);
      if (!empty($parsed)) {
        $questions[$parsed['id']] = $parsed;
      }
    }
    return $questions;
  }

  /**
   * Get questions filtered by topic code(s).
   *
   * @param string|array $topic_codes
   *   Single topic code or array of topic codes.
   *
   * @return array
   *   Filtered questions array (same structure as getAllQuestions).
   */
  public function getQuestionsByTopic($topic_codes): array {
    return []; // Topic filtering not applicable for account-level only fetch.
  }

  /**
   * Clear the account-level questions cache.
   */
  public function clearCache(): void {
    \Drupal::service('cache_tags.invalidator')->invalidateTags([self::CACHE_TAG]);
    $this->logger->info('GovDelivery questions cache cleared.');
  }

  /**
   * Parse a question XML response into a structured array.
   *
   * @param \SimpleXMLElement $xml
   *   Question XML.
   * @param string $account_base
   *   API base URL.
   * @param string $username
   *   API username.
   * @param string $password
   *   API password.
   *
   * @return array|null
   *   Question array or NULL on error.
   */
  protected function parseQuestionXml(\SimpleXMLElement $xml): ?array {
    $id_node = $xml->xpath('//*[local-name()="to-param"]');
    $name_node = $xml->xpath('//*[local-name()="name"]');
    $text_node = $xml->xpath('//*[local-name()="question-text"]');
    $type_node = $xml->xpath('//*[local-name()="response-type"]');

    $id = !empty($id_node[0]) ? (string) $id_node[0] : '';
    $name = !empty($name_node[0]) ? (string) $name_node[0] : '';
    $text = !empty($text_node[0]) ? (string) $text_node[0] : '';
    $type = !empty($type_node[0]) ? (string) $type_node[0] : '';

    if ($id === '' || $name === '') {
      return NULL;
    }

    // Collect answer choices if present (for select questions).
    $answers = [];
    $answer_nodes = $xml->xpath('//*[local-name()="answers"]/*[local-name()="answer"]');
    if (!empty($answer_nodes)) {
      foreach ($answer_nodes as $ans) {
        $txt_node = $ans->xpath('./*[local-name()="answer-text"]');
        $aid_node = $ans->xpath('./*[local-name()="to-param"]');
        $aText = !empty($txt_node[0]) ? (string) $txt_node[0] : '';
        $aId = !empty($aid_node[0]) ? (string) $aid_node[0] : '';
        if ($aText !== '' && $aId !== '') {
          $answers[] = [
            'id' => $aId,
            'text' => $aText,
          ];
        }
      }
    }

    return [
      'id' => $id,
      'name' => $name,
      'question_text' => $text,
      'response_type' => $type,
      'topics' => [],
      'answers' => $answers,
    ];
  }

  // Topic-specific utilities removed for simplified account-level public questions.

}
