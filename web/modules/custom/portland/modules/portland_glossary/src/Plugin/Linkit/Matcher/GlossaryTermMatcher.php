<?php

namespace Drupal\portland_glossary\Plugin\Linkit\Matcher;

use Drupal\linkit\MatcherBase;
use Drupal\linkit\Suggestion\SuggestionCollection;
use Drupal\linkit\Suggestion\EntitySuggestion;
use Drupal\linkit\Suggestion\DescriptionSuggestion;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a Linkit matcher for glossary terms.
 *
 * @Matcher(
 *   id = "glossary_term_matcher",
 *   label = @Translation("Glossary Term"),
 *   target_entity = "node",
 *   substitution_type = "glossary_term"
 * )
 */
class GlossaryTermMatcher extends MatcherBase
{

    /**
     * {@inheritdoc}
     */
    public function execute($string)
    {
        return $this->getMatches($string);
    }

    /**
     * Custom method to get matches for glossary terms.
     */
    public function getMatches($string)
    {
        $suggestions = new SuggestionCollection();

        // First, find the term ID for "Glossary Term".
        $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $terms = $term_storage->loadByProperties(['name' => 'Glossary Term']);
        $term_ids = array_keys($terms);

        if (empty($term_ids)) {
            // No matching term found, return empty suggestions.
            return $suggestions;
        }

        // Query for published 'content_fragment' nodes referencing the found term.
        $storage = \Drupal::entityTypeManager()->getStorage('node');
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('type', 'content_fragment')
            ->condition('status', 1)
            ->condition('field_fragment_type', $term_ids, 'IN')
            ->condition('title', $string, 'CONTAINS')
            ->range(0, 10);

        $nids = $query->execute();
        if ($nids) {
            $nodes = $storage->loadMultiple($nids);
            foreach ($nodes as $node) {
                if ($node instanceof \Drupal\node\NodeInterface) {
                    $suggestion = new EntitySuggestion(); //DescriptionSuggestion();
                    // Limit label to 90 characters with ellipsis if needed.
                    $label = $node->label();
                    if (mb_strlen($label) > 90) {
                        $label = mb_substr($label, 0, 87) . '…';
                    }
                    $suggestion->setLabel($label);
                    $suggestion->setPath($node->toUrl()->toString());
                    // Use the node path as the description.
                    $description = $node->toUrl()->toString();
                    $suggestion->setDescription($description);
                    $suggestion->setGroup($this->t('Glossary'));
                    $suggestion->setSubstitutionId('glossary_term');
                    $suggestion->setEntityTypeId($node->getEntityTypeId());
                    $suggestion->setEntityUuid($node->uuid());
                    $suggestions->addSuggestion($suggestion);
                }
            }
        }

        return $suggestions;
    }
}

// original file
