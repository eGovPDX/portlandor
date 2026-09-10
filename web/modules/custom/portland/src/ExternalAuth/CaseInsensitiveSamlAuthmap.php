<?php

namespace Drupal\portland\ExternalAuth;

use Drupal\externalauth\Authmap;

/**
 * Matches SAML authnames case-insensitively.
 *
 * Handles the email claim from Entra ID in similar fashion to Drupal core,
 * which allows users to log in with a case-insensitive email address.
 */
class CaseInsensitiveSamlAuthmap extends Authmap {

  /**
   * The provider name this case-insensitive matching applies to.
   */
  const PROVIDER = 'samlauth';

  /**
   * {@inheritdoc}
   */
  public function getUid(string $authname, string $provider) {
    $uid = parent::getUid($authname, $provider);
    if ($uid !== FALSE || $provider !== static::PROVIDER) {
      return $uid;
    }

    // No byte-for-byte match was found for the SAML provider. Fall back to
    // a case-insensitive lookup so a casing change from the IdP doesn't
    // orphan an existing account.
    $query = $this->connection->select('authmap', 'am')
      ->fields('am', ['uid', 'authname', 'provider'])
      ->condition('provider', $provider)
      ->range(0, 1);
    $query->where('LOWER([authname]) = LOWER(:authname)', [':authname' => $authname]);
    $row = $query->execute()->fetchObject();

    if ($row && strcasecmp($row->authname, $authname) === 0 && $row->provider === $provider) {
      return (int) $row->uid;
    }
    return FALSE;
  }

}
