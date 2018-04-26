<?php

namespace Drupal\simple_oauth\Repositories;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\RoleInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Drupal\simple_oauth\Entities\ScopeEntity;

class ScopeRepository implements ScopeRepositoryInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ScopeRepository constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getScopeEntityByIdentifier($scope_identifier) {
    $role = $this->entityTypeManager
      ->getStorage('user_role')
      ->load($scope_identifier);
    if (!$role) {
      return NULL;
    }

    return $this->scopeFactory($role);
  }

  /**
   * {@inheritdoc}
   *
   * This will remove any role that is not associated to the identified user and
   * add all the roles configured in the client.
   */
  public function finalizeScopes(array $scopes, $grant_type, ClientEntityInterface $client_entity, $user_identifier = NULL) {
    $default_user = NULL;
    try {
      $default_user = $client_entity->getDrupalEntity()->get('user_id')->entity;
    }
    catch (\InvalidArgumentException $e) {
      // Do nothing. This means that simple_oauth_extras is not enabled.
    }
    /** @var \Drupal\user\UserInterface $user */
    $user = $user_identifier
      ? $this->entityTypeManager->getStorage('user')->load($user_identifier)
      : $default_user;
    if (!$user) {
      return [];
    }

    $role_ids = $user->getRoles();
    // Given a user, only allow the roles that the user already has, regardless
    // of what has been requested.
    $scopes = array_filter($scopes, function (ScopeEntityInterface $scope) use ($role_ids) {
      return in_array($scope->getIdentifier(), $role_ids);
    });

    // Make sure that the Authenticated role is added as well.
    $scopes = $this->addRoleToScopes($scopes, RoleInterface::AUTHENTICATED_ID);
    // Make sure that the client roles are added to the scopes as well.
    /** @var \Drupal\consumers\Entity\Consumer $client_drupal_entity */
    $client_drupal_entity = $client_entity->getDrupalEntity();
    $scopes = array_reduce($client_drupal_entity->get('roles')->getValue(), function ($scopes, $role_id) {
      return $this->addRoleToScopes($scopes, $role_id['target_id']);
    }, $scopes);

    return $scopes;
  }

  /**
   * Build a scope entity.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The associated role.
   *
   * @return \League\OAuth2\Server\Entities\ScopeEntityInterface
   *   The initialized scope entity.
   */
  protected function scopeFactory(RoleInterface $role) {
    return new ScopeEntity($role);
  }

  /**
   * Add an additional scope if it's not present.
   *
   * @param \League\OAuth2\Server\Entities\ScopeEntityInterface[] $scopes
   *   The list of scopes.
   * @param string $additional_role_id
   *   The role ID to add as a scope.
   *
   * @return \League\OAuth2\Server\Entities\ScopeEntityInterface[]
   *   The modified list of scopes.
   */
  protected function addRoleToScopes(array $scopes, $additional_role_id) {
    $role_storage = $this->entityTypeManager->getStorage('user_role');
    // Only add the role if it's not already in the list.
    $found = array_filter($scopes, function (ScopeEntityInterface $scope) use ($additional_role_id) {
      return $scope->getIdentifier() == $additional_role_id;
    });
    if (empty($found)) {
      // If it's not there, then add the authenticated role.
      $additional_role = $role_storage->load($additional_role_id);
      array_push($scopes, $this->scopeFactory($additional_role));
    }

    return $scopes;
  }

}
