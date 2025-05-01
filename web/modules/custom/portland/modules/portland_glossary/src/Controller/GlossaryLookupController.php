<?php

namespace Drupal\portland_glossary\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class GlossaryLookupController extends ControllerBase {

  public function lookup($uuid) {
    // Load the node by UUID.
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['uuid' => $uuid, 'type' => 'glossary_term']);

    if (empty($node)) {
      throw new NotFoundHttpException("No glossary term found for UUID '$uuid'.");
    }

    // Get the first node (there should only be one).
    $node = reset($node);

    // Ensure the node is loaded as a Node entity.
    if (!$node instanceof Node) {
      throw new NotFoundHttpException("The loaded entity is not a valid Node.");
    }

    // Prepare the result.
    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString();
    $result = [
      'nid' => $node->id(),
      'title' => $node->label(),
      'definition' => $node->get('field_body_content')->value, // Updated to use field_body_content
      'short_definition' => $node->get('field_summary')->value, // Updated to use field_summary
      'url' => $url,
      'pronunciation' => $node->get('field_english_pronunciation')->value,
    ];

    return new JsonResponse([$result]);
  }

}
