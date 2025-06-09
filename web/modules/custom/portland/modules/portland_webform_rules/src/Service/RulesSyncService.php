<?php

namespace Drupal\portland_webform_rules\Service;

use Drupal\portland_webform_rules\Client\SmartsheetRulesClient;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;

/**
 * Handles syncing Smartsheet rules into Content Fragment nodes.
 */
class RulesSyncService {

  protected ClientInterface $httpClient;
  protected $secrets;
  protected EntityTypeManagerInterface $entityTypeManager;

  private const SHEET_IDS = [
    'permits' => 'abc123',     // Replace with your actual sheet ID
    'documents' => 'def456',
    'questions' => 'ghi789',
    'rules' => 'jkl012',
  ];

  public function __construct(
    ClientInterface $http_client,
    $secrets,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->httpClient = $http_client;
    $this->secrets = $secrets;
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('secrets'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Trigger the sync process.
   */
  public function sync() {
    $sheets_data = [];

    foreach (self::SHEET_IDS as $key => $sheet_id) {
      $client = new SmartsheetRulesClient($sheet_id);
      $sheets_data[$key] = $client->getSheet(['includeAll' => 'true']);
    }

    $rules_json_per_permit = $this->transformToRulesJson($sheets_data);

    foreach ($rules_json_per_permit as $permit_type => $json_blob) {
      $this->storeJsonInNode($permit_type, $json_blob);
    }
  }

  /**
   * Transforms sheet data into a rules JSON blob per permit.
   */
  protected function transformToRulesJson(array $sheets_data): array {
    // Implement your own transformation logic here.
    return [];
  }

  /**
   * Stores or updates the JSON blob in a Content Fragment node.
   */
  protected function storeJsonInNode(string $permit_type, array $json_blob): void {
    $title = "Rules: $permit_type";

    $existing = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => 'content_fragment',
      'title' => $title,
    ]);

    $node = reset($existing) ?: Node::create(['type' => 'content_fragment']);
    $node->title = $title;
    $node->field_rules_json = json_encode($json_blob);
    $node->status = 1;
    $node->save();
  }
}
