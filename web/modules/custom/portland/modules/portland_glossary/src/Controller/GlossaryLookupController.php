<?php

namespace Drupal\portland_glossary\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class GlossaryLookupController extends ControllerBase {

  public function lookup($term) {
    $matches = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'glossary_term')
      ->condition('title', $term, 'CONTAINS')  // case-insensitive match workaround
      ->execute();

    if (empty($matches)) {
      throw new NotFoundHttpException("No glossary term found for '$term'.");
    }

    $nodes = Node::loadMultiple($matches);
    $results = [];

    foreach ($nodes as $node) {
      if (strcasecmp($node->getTitle(), $term) === 0) {
        $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString();
        $results[] = [
          'nid' => $node->id(),
          'title' => $node->getTitle(),
          'definition' => $node->get('field_body_content')->value, // Updated to use field_body_content
          'short_definition' => $node->get('field_summary')->value, // Updated to use field_summary
          'url' => $url,
          'pronunciation' => $node->get('field_english_pronunciation')->value,
        ];
      }
    }

    if (empty($results)) {
      throw new NotFoundHttpException("No exact glossary term match found for '$term'.");
    }

    return new JsonResponse($results);
  }

}
