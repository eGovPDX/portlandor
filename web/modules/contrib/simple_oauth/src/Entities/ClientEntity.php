<?php

namespace Drupal\simple_oauth\Entities;

use Drupal\consumers\Entity\Consumer;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface {

  use EntityTrait, ClientTrait;

  /**
   * @var \Drupal\consumers\Entity\Consumer
   */
  protected $entity;

  /**
   * ClientEntity constructor.
   *
   * @param \Drupal\consumers\Entity\Consumer $entity
   *   The Drupal entity.
   */
  public function __construct(Consumer $entity) {
    $this->entity = $entity;
    $this->setIdentifier($entity->uuid());
    $this->setName($entity->label());
    if ($entity->hasField('redirect')) {
      $this->redirectUri = $entity->get('redirect')->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalEntity() {
    return $this->entity;
  }

}
