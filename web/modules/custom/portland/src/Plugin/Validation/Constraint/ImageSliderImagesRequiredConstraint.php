<?php

namespace Drupal\portland\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Make sure Image Slider media has both images uploaded.
 *
 * @Constraint(
 *   id = "ImageSliderImagesRequired",
 *   label = @Translation("Make sure Image Slider media has both images uploaded.", context = "Validation"),
 *   type = "entity:media"
 * )
 */
class ImageSliderImagesRequiredConstraint extends Constraint {

  /**
   * Message shown when fewer than 2 images are uploaded.
   *
   * @var string
   */
  public $message = 'Image Slider requires 2 images. Please upload both images.';

}
