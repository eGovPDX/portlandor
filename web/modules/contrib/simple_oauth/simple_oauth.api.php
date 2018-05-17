<?php

/**
 * @file
 * Hooks specific to the Simple OAuth module.
 */

/**
 * @defgroup simple_oauth Simple Oauth: Hooks
 * @{
 */

/**
 * Alter the private claims to prepare convert to JWT token.
 *
 * @param $private_claims
 *   The private claims array to be altered.
 * @param \Drupal\simple_oauth\Entities\AccessTokenEntity $access_token_entity
 *
 * @see \Drupal\simple_oauth\Entities\AccessTokenEntity::convertToJWT()
 */
function hook_simple_oauth_private_claims_alter(&$private_claims, \Drupal\simple_oauth\Entities\AccessTokenEntity $access_token_entity) {
  $user_id = $access_token_entity->getUserIdentifier();
  $user = \Drupal\user\Entity\User::load($user_id);
  $private_claims = [
    'mail' => $user->getEmail(),
    'username' => $user->getAccountName(),
  ];
}

/**
 * @} End of "defgroup simple_oauth".
 */
