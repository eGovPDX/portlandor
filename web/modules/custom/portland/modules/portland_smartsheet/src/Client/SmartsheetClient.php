<?php

namespace Drupal\portland_smartsheet\Client;
use GuzzleHttp\Client;

/**
 * Class SmartsheetClient
 * @package Drupal\portland_smartsheet\Client
 */
class SmartsheetClient {
  private Client $client;
  private string $sheet_id;

  public function __construct($sheet_id) {
    if ($sheet_id === '') throw new \Exception('No sheet ID passed to SmartsheetClient');

    $this->$sheet_id = $sheet_id;
    $config = \Drupal::config('portland_smartsheet.adminsettings');
    $access_token = $config->get('access_token');

    $this->client = new Client([
      'base_uri' => "https://api.smartsheet.com/2.0/sheets/{$sheet_id}/",
      'headers' => [
        'Authorization' => "Bearer {$access_token}"
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
    if (isset($body->resultCode) && $body->resultCode === 3) {
      throw new \Exception('Smartsheet API: partial success');
    }
    if (isset($body->errorCode)) {
      $details = isset($body->details) ? json_encode($body->details) : "";
      throw new \Exception("Smartsheet API: error code {$body->errorCode}, {$body->message}, {$details}");
    }

    return $body->result ?? $body->data ?? $body;
  }

  public function addAttachment($row_id, $attachment) {
    if (!array_key_exists('filecontent', $attachment)
      || !array_key_exists('filename', $attachment)
      || !array_key_exists('filemime', $attachment)) {
      throw new \Exception("Smartsheet API: attachment requires the following properties: filecontent, filename, filemime");
    }

    return $this->handleResponse($this->client->request('POST', "rows/{$row_id}/attachments", [
      'multipart' => [
        [
          'name' => 'file',
          'contents' => $attachment['filecontent'],
          'filename' => $attachment['filename'],
          'headers' => [
            'Content-Type' => $attachment['filemime'],
          ],
        ],
      ],
    ]));
  }

  public function addRows($data) {
    return $this->handleResponse($this->client->request('POST', 'rows', ['json' => $data]));
  }

  public function listAllColumns() {
    return $this->handleResponse($this->client->request('GET', 'columns?includeAll=true'));
  }

  public function listAllFilters() {
    return $this->handleResponse($this->client->request('GET', 'filters?includeAll=true'));
  }

  public function getAttachment($attachment_id) {
    return $this->handleResponse($this->client->request('GET', "attachments/{$attachment_id}"));
  }

  public function getSheet($query = []) {
    return $this->handleResponse($this->client->request('GET', '?' . http_build_query($query)));
  }
}
