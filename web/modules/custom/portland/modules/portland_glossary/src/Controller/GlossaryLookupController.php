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
    $definition = $node->get('field_body_content')->value;
    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString();

    // Prepare see_also array.
    $see_also = [];
    if ($node->hasField('field_see_also') && !$node->get('field_see_also')->isEmpty()) {
      foreach ($node->get('field_see_also')->referencedEntities() as $ref_node) {
        $see_also[] = [
          'title' => $ref_node->label(),
          'url' => Url::fromRoute('entity.node.canonical', ['node' => $ref_node->id()], ['absolute' => TRUE])->toString(),
        ];
      }
    }

    $result = [
      'nid' => $node->id(),
      'title' => $node->label(),
      // 'definition' => $definition, // Do not include the long definition in the JSON
      'short_definition' => $node->get('field_summary')->value, // Updated to use field_summary
      'url' => $url,
      'pronunciation' => $node->get('field_english_pronunciation')->value,
      'has_long_definition' => !empty($definition),
      'see_also' => $see_also,
    ];

    return new JsonResponse([$result]);
  }

}
