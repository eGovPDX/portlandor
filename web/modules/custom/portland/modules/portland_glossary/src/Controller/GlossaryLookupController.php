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
        $body_field = $node->get('body');
        $results[] = [
          'nid' => $node->id(),
          'title' => $node->getTitle(),
          'definition' => $body_field->value,
          'short_definition' => $body_field->summary, // Changed "summary" to "short_definition"
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
