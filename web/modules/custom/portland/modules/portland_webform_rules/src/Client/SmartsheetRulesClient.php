<?php

namespace Drupal\portland_webform_rules\Client;

use GuzzleHttp\Client;

class SmartsheetRulesClient {
  private Client $client;
  private string $sheet_id;

  public function __construct($sheet_id) {
    if (empty($sheet_id)) {
      throw new \Exception('No sheet ID passed to SmartsheetRulesClient');
    }

    $this->sheet_id = $sheet_id;
    $config = \Drupal::config('portland_smartsheet.adminsettings');
    $access_token = $config->get('access_token');

    $this->client = new Client([
      'base_uri' => "https://api.smartsheet.com/2.0/sheets/{$sheet_id}/",
      'headers' => [
        'Authorization' => "Bearer {$access_token}",
      ],
      'http_errors' => FALSE,
    ]);
  }

  private function handleResponse($res) {
    $status_code = $res->getStatusCode();
    $body = json_decode($res->getBody());
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception("Smartsheet API: JSON decode error, HTTP {$status_code}");
    }

    if (isset($body->errorCode)) {
      $details = isset($body->details) ? json_encode($body->details) : '';
      throw new \Exception("Smartsheet API: error code {$body->errorCode}, {$body->message}, {$details}");
    }

    return $body->result ?? $body->data ?? $body;
  }

  public function getSheet(array $query = []) {
    $query_string = !empty($query) ? ('?' . http_build_query($query)) : '';
    return $this->handleResponse($this->client->request('GET', $query_string));
  }
}
