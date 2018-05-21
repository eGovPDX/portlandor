<?php

namespace Drupal\simple_oauth\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the OAuth2 Token Type entity.
 *
 * @ConfigEntityType(
 *   id = "oauth2_token_type",
 *   label = @Translation("OAuth2 Token Type"),
 *   handlers = {
 *     "access" = "Drupal\simple_oauth\LockableConfigEntityAccessControlHandler"
 *   },
 *   config_prefix = "oauth2_token.bundle",
 *   admin_permission = "administer simple_oauth entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class Oauth2TokenType extends ConfigEntityBase implements Oauth2TokenTypeInterface {

  use ConfigEntityLockableTrait;

  /**
   * The Access Token Type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Access Token Type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Access Token Type label.
   *
   * @var string
   */
  protected $description = '';

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
  }

}
