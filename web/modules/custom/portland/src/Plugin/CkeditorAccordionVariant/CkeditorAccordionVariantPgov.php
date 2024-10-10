<?php

namespace Drupal\portland\Plugin\CkeditorAccordionVariant;

use Drupal\ckeditor_accordion\Plugin\CkeditorAccordionVariantBase;


/**
 * @CkeditorAccordionVariant(
 *  id = "ckeditor_accordion_variant_pgov",
 *  label = @Translation("Portland.gov"),
 *  description = @Translation("Custom HTML structure which uses <div> <h3> <div> style tags."),
 * )
 */
class CkeditorAccordionVariantPgov extends CkeditorAccordionVariantBase{

  /**
   * {@inheritdoc}
   */
  protected $list_tag = [
    'tag' => 'div',
    'attributes' => [],
  ];

  /**
   * {@inheritdoc}
   */
  protected $title_tag = [
    'tag' => 'h3',
    'attributes' => [],
  ];

  /**
   * {@inheritdoc}
   */
  protected $description_tag = [
    'tag' => 'div',
    'attributes' => [],
  ];

}
