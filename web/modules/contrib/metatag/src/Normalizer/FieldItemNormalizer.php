<?php

namespace Drupal\metatag\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts the Metatag field item object structure to METATAG array structure.
 */
class FieldItemNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\metatag\Plugin\Field\FieldType\MetatagFieldItem';

  /**
   * {inheritDoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    return t('Metatags are normalized in the metatag field.');
  }

}
