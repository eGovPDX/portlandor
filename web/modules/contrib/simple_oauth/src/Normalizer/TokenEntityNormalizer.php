<?php

namespace Drupal\simple_oauth\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\serialization\Normalizer\NormalizerBase;

class TokenEntityNormalizer extends NormalizerBase implements TokenEntityNormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string|array
   */
  protected $supportedInterfaceOrClass = '\League\OAuth2\Server\Entities\TokenInterface';

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($token_entity, $format = NULL, array $context = []) {
    /** @var \League\OAuth2\Server\Entities\TokenInterface $token_entity */

    $scopes = array_map(function ($scope_entity) {
      /** @var \League\OAuth2\Server\Entities\ScopeEntityInterface $scope_entity */
      return ['target_id' => $scope_entity->getIdentifier()];
    }, $token_entity->getScopes());

    /** @var \Drupal\simple_oauth\Entities\ClientEntityInterface $client */
    $client = $token_entity->getClient();
    $client_drupal_entity = $client->getDrupalEntity();

    return [
      'auth_user_id' => ['target_id' => $token_entity->getUserIdentifier()],
      'client' => ['target_id' => $client_drupal_entity->id()],
      'scopes' => $scopes,
      'value' => $token_entity->getIdentifier(),
      'expire' => $token_entity->getExpiryDateTime()->format('U'),
      'status' => TRUE,
    ];
  }

}
