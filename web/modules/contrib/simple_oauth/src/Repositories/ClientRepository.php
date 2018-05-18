<?php

namespace Drupal\simple_oauth\Repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Drupal\simple_oauth\Entities\ClientEntity;

class ClientRepository implements ClientRepositoryInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordChecker;

  /**
   * Constructs a ClientRepository object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PasswordInterface $password_checker) {
    $this->entityTypeManager = $entity_type_manager;
    $this->passwordChecker = $password_checker;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientEntity($client_identifier, $grant_type, $client_secret = NULL, $must_validate_secret = TRUE) {
    $client_drupal_entities = $this->entityTypeManager
      ->getStorage('consumer')
      ->loadByProperties(['uuid' => $client_identifier]);

    // Check if the client is registered.
    if (empty($client_drupal_entities)) {
      return NULL;
    }
    /** @var \Drupal\consumers\Entity\Consumer $client_drupal_entity */
    $client_drupal_entity = reset($client_drupal_entities);

    $secret = $client_drupal_entity->get('secret')->value;
    if (
      $must_validate_secret && $client_drupal_entity->get('confidential')->value &&
      $this->passwordChecker->check($client_secret, $secret) === FALSE
    ) {
      return NULL;
    }

    return new ClientEntity($client_drupal_entity);
  }

}
