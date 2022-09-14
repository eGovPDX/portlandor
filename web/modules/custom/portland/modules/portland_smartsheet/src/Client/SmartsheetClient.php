<?php

namespace Drupal\portland_smartsheet\Client;
use GuzzleHttp\Client;

/**
 * Class SmartsheetClient
 * @package Drupal\portland_smartsheet\Client
 */
class SmartsheetClient {
  private \GuzzleHttp\Client $client;
  private string $sheet_id;

  public function __construct($sheet_id) {
    $this->$sheet_id = $sheet_id;
    $config = \Drupal::config("portland_smartsheet.adminsettings");
    $access_token = $config->get("access_token");

    $this->client = new Client([
      "base_uri" => "https://api.smartsheet.com/2.0/sheets/{$sheet_id}/",
      "headers" => [
        "Authorization" => "Bearer {$access_token}"
      ],
      "http_errors" => FALSE
    ]);
  }

  private function handleResponse($res) {
    $status_code = $res->getStatusCode();

    $body = json_decode($res->getBody());
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \Exception("Smartsheet API: JSON decode error, HTTP {$status_code}");
    }
    if (isset($body->resultCode) && $body->resultCode === 3) {
      throw new \Exception("Smartsheet API: partial success");
    }
    if (isset($body->errorCode)) {
      $details = isset($body->details) ? json_encode($body->details) : "";
      throw new \Exception("Smartsheet API: error code {$body->errorCode}, {$body->message}, {$details}");
    }

    return $body->result ?? $body->data ?? $body;
  }

  public function addRow($data) {
    return $this->handleResponse($this->client->request('POST', 'rows', ['json' => $data]));
  }

  public function listAllColumns() {
    return $this->handleResponse($this->client->request('GET', 'columns?includeAll=true'));
  }

  public function getSheet() {
    return $this->handleResponse($this->client->request('GET', '?rowIds=0&columnIds=0'));
  }
}
