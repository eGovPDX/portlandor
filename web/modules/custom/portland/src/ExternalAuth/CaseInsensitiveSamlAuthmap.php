<?php

namespace Drupal\portland\ExternalAuth;

use Drupal\Core\Database\Connection;
use Drupal\externalauth\AuthmapInterface;
use Drupal\user\UserInterface;

/**
 * Decorates the authmap service to match SAML authnames case-insensitively.
 *
 * Handles the email claim from Entra ID in similar fashion to Drupal core's user_email module, which allows users to log in with a case-insensitive email address.
 */
class CaseInsensitiveSamlAuthmap implements AuthmapInterface {

  /**
   * The provider name this case-insensitive matching applies to.
   */
  const PROVIDER = 'samlauth';

  /**
   * The decorated authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $inner;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a CaseInsensitiveSamlAuthmap object.
   *
   * @param \Drupal\externalauth\AuthmapInterface $inner
   *   The decorated authmap service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(AuthmapInterface $inner, Connection $database) {
    $this->inner = $inner;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function save(UserInterface $account, string $provider, string $authname, $data = NULL) {
    return $this->inner->save($account, $provider, $authname, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function get(int $uid, string $provider) {
    return $this->inner->get($uid, $provider);
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthData(int $uid, string $provider) {
    return $this->inner->getAuthData($uid, $provider);
  }

  /**
   * {@inheritdoc}
   */
  public function getAll($uid): array {
    return $this->inner->getAll($uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getUid(string $authname, string $provider) {
    $uid = $this->inner->getUid($authname, $provider);
    if ($uid !== FALSE || $provider !== static::PROVIDER) {
      return $uid;
    }

    // No byte-for-byte match was found for the SAML provider. Fall back to
    // a case-insensitive lookup so a casing change from the IdP doesn't
    // orphan an existing account.
    $row = $this->database->select('authmap', 'am')
      ->fields('am', ['uid', 'authname', 'provider'])
      ->condition('provider', $provider)
      ->range(0, 1);
    $row->where('LOWER([authname]) = LOWER(:authname)', [':authname' => $authname]);
    $row = $row->execute()->fetchObject();

    if ($row && strcasecmp($row->authname, $authname) === 0 && $row->provider === $provider) {
      return (int) $row->uid;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(int $uid, ?string $provider = NULL) {
    $this->inner->delete($uid, $provider);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteProvider(string $provider) {
    $this->inner->deleteProvider($provider);
  }

}
