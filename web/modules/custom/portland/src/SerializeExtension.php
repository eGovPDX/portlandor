<?php

namespace Drupal\portland;

use \Symfony\Component\Serializer\SerializerInterface;
use \Twig\Extension\AbstractExtension;
use \Twig\TwigFilter;

/**
 * Implements a Twig extension that allows for using the Symfony serialization interface.
 * Requires csv_serialization module.
 */
class SerializeExtension extends AbstractExtension {
  protected $serializer;

  public function __construct(SerializerInterface $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   * Let Drupal know the name of your extension
   * must be unique name, string
   */
  public function getName() {
    return 'portland.serialize_extension';
  }

  public function getFilters() {
    return [
      new TwigFilter('serialize', [$this, 'serialize']),
    ];
  }

  public function serialize($data, string $format = 'json', array $context = []): string {
    return $this->serializer->serialize($data, $format, $context);
  }
}
