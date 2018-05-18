<?php

namespace Drupal\simple_oauth\Service;

/**
 * @internal
 */
class KeyGenerator {

  const CERT_CONFIG = [
    "digest_alg" => "sha512",
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
  ];

  /**
   * Generate a private and public key.
   *
   * @return array with the generated public and private key
   */
  public static function generateKeys() {
    // Generate Resource.
    $resource = openssl_pkey_new(KeyGenerator::CERT_CONFIG);
    // Get Private Key.
    openssl_pkey_export($resource, $pkey);
    // Get Public Key.
    $pubkey = openssl_pkey_get_details($resource);

    return [
      'private' => $pkey,
      'public' => $pubkey['key'],
    ];
  }

}
