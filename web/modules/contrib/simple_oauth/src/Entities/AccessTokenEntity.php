<?php

namespace Drupal\simple_oauth\Entities;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface {

  use AccessTokenTrait, TokenEntityTrait, EntityTrait;

  /**
   * {@inheritdoc}
   */
  public function convertToJWT(CryptKey $privateKey) {
    $private_claims = [];
    \Drupal::moduleHandler()->alter('simple_oauth_private_claims', $private_claims, $this);
    if (!is_array($private_claims)) {
      $message = 'An implementation of hook_simple_oauth_private_claims_alter ';
      $message .= 'returns an invalid $private_claims value. $private_claims ';
      $message .= 'must be an array.';
      throw new \InvalidArgumentException($message);
    }
    $builder = (new Builder())
      ->setAudience($this->getClient()->getIdentifier())
      ->setId($this->getIdentifier(), TRUE)
      ->setIssuedAt(time())
      ->setNotBefore(time())
      ->setExpiration($this->getExpiryDateTime()->getTimestamp())
      ->setSubject($this->getUserIdentifier())
      ->set('scopes', $this->getScopes());

    foreach ($private_claims as $claim_name => $value) {
      $builder->set($claim_name, $value);
    }

    $key = new Key($privateKey->getKeyPath(), $privateKey->getPassPhrase());
    $token = $builder->sign(new Sha256(), $key)->getToken();
    return $token;
  }

}
