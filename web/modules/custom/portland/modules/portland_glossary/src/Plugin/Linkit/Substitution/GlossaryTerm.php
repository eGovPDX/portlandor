<?php

namespace Drupal\portland_glossary\Plugin\Linkit\Substitution;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\linkit\SubstitutionInterface;

/**
 * A substitution plugin for glossary terms.
 *
 * @Substitution(
 *   id = "glossary_term",
 *   label = @Translation("Glossary Term"),
 * )
 */
class GlossaryTerm extends PluginBase implements SubstitutionInterface {

  /**
   * {@inheritdoc}
   */
  public function getUrl(EntityInterface $entity) {
    return $entity->toUrl('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->hasLinkTemplate('canonical');
  }

}