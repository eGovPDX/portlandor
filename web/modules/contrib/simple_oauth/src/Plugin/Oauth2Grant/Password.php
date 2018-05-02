<?php

namespace Drupal\simple_oauth\Plugin\Oauth2Grant;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\simple_oauth\Plugin\Oauth2GrantBase;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Oauth2Grant(
 *   id = "password",
 *   label = @Translation("Password")
 * )
 */
class Password extends Oauth2GrantBase {

  /**
   * @var \League\OAuth2\Server\Repositories\UserRepositoryInterface
   */
  protected $userRepository;

  /**
   * @var \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface
   */
  protected $refreshTokenRepository;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserRepositoryInterface $user_repository, RefreshTokenRepositoryInterface $refresh_token_repository, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userRepository = $user_repository;
    $this->refreshTokenRepository = $refresh_token_repository;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_oauth.repositories.user'),
      $container->get('simple_oauth.repositories.refresh_token'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getGrantType() {
    $grant = new PasswordGrant($this->userRepository, $this->refreshTokenRepository);
    $settings = $this->configFactory->get('simple_oauth.settings');
    $grant->setRefreshTokenTTL(new \DateInterval(sprintf('PT%dS', $settings->get('refresh_token_expiration'))));
    return $grant;
  }

}
