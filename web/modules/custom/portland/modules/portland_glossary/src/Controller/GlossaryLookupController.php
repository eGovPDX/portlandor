<?php

namespace Drupal\portland_glossary\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;

class GlossaryLookupController extends ControllerBase {
  public function lookup(string $uuids) {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    // Load the nodes by UUID.
    $query = $node_storage
      ->getQuery()
      // We'll do a manual access check since published is all we care about.
      ->accessCheck(FALSE)
      ->condition('type', 'glossary_term')
      ->condition('uuid', explode(',', $uuids), 'IN');
    // If the user is anonymous, only show published nodes.
    if ($this->currentUser()->isAnonymous()) {
      $query->condition('status', 1);
    }

    $nids = $query->execute();
    $nodes = $nids ? $node_storage->loadMultiple($nids) : [];

    $response = new CacheableJsonResponse();
    // Results keyed by UUID.
    $result = [];
    foreach ($nodes as $node) {
      // Ensure the node is loaded as a Node entity.
      if (!$node instanceof Node) continue;

      // Prepare see_also array.
      $see_also = array_map(fn($ref_node) => [
        'title' => $ref_node->label(),
        'url' => $ref_node->toUrl()->toString(),
      ], $node->get('field_see_also')->referencedEntities());

      $result[$node->uuid()] = [
        'nid' => $node->id(),
        'title' => $node->label(),
        'short_definition' => $node->get('field_summary')->value,
        'url' => $node->toUrl()->toString(),
        'pronunciation' => $node->get('field_english_pronunciation')->value,
        'has_long_definition' => !empty($node->get('field_body_content')->value),
        'see_also' => $see_also,
        'published' => $node->isPublished(),
      ];
      $response->addCacheableDependency($node);
    }

    $response->getCacheableMetadata()->addCacheContexts(['url.query_args:uuids', 'user.roles:authenticated']);
    $response->setData($result);
    return $response;
  }

}
