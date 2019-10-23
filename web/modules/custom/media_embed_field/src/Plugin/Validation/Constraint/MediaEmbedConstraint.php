<?php

namespace Drupal\media_embed_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the media embed field.
 *
 * @Constraint(
 *   id = "MediaEmbedValidation",
 *   label = @Translation("MediaEmbed provider constraint", context = "Validation"),
 * )
 */
class MediaEmbedConstraint extends Constraint {

  /**
   * Message shown when a media provider is not found.
   *
   * @var string
   */
  public $message = 'Could not find a media provider to handle the given URL.';

}
