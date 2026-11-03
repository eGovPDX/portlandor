<?php

namespace Drupal\portland\Hook;

use Drupal\Core\Hook\Attribute\Hook;

class EntityHooks {
  /**
   * Alter news bundle field info to add accordion constraint to the FAQ field.
   */
  #[Hook('entity_bundle_field_info_alter')]
  public function entityBundleFieldInfoAlter(array &$fields, \Drupal\Core\Entity\EntityTypeInterface $entity_type, string $bundle) {
    if ($entity_type->id() === 'node' && $bundle === 'news') {
      if (isset($fields['field_faq'])) {
        $fields['field_faq']->addConstraint('SingleAccordionBodyContent', []);
      }
    }
  }
}
