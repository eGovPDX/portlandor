<?php

namespace Drupal\portland_glossary\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;

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
        $results[] = [
          'nid' => $node->id(),
          'title' => $node->getTitle(),
          'definition' => $node->get('body')->value,
        ];
      }
    }

    if (empty($results)) {
      throw new NotFoundHttpException("No exact glossary term match found for '$term'.");
    }

    return new JsonResponse($results);
  }
}
