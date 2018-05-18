<?php

namespace Drupal\simple_oauth_extras\Plugin\Oauth2Grant;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simple_oauth\Plugin\Oauth2GrantBase;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Oauth2Grant(
 *   id = "authorization_code",
 *   label = @Translation("Authorization Code")
 * )
 */
class AuthorizationCode extends Oauth2GrantBase {

  /**
   * @var \League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface
   */
  protected $authCodeRepository;

  /**
   * @var \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface
   */
  protected $refreshTokenRepository;

  /**
   * @var \DateTime
   */
  protected $authCodeExpiration;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AuthCodeRepositoryInterface $auth_code_repository, RefreshTokenRepositoryInterface $refresh_token_repository, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $settings = $config_factory->get('simple_oauth.settings');
    $this->authCodeRepository = $auth_code_repository;
    $this->refreshTokenRepository = $refresh_token_repository;
    // TODO: Make this configurable and not just the same as the access toke expiration.
    $this->authCodeExpiration = new \DateInterval(sprintf('PT%dS', $settings->get('access_token_expiration')));
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_oauth_extras.repositories.auth_code'),
      $container->get('simple_oauth.repositories.refresh_token'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantType() {
    return new AuthCodeGrant(
      $this->authCodeRepository,
      $this->refreshTokenRepository,
      $this->authCodeExpiration
    );
  }

}
