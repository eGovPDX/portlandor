<?php

namespace Drupal\simple_oauth\Plugin;

use Defuse\Crypto\Core;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Site\Settings;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;

/**
 * Provides the OAuth2 Grant plugin manager.
 */
class Oauth2GrantManager extends DefaultPluginManager implements Oauth2GrantManagerInterface {

  /**
   * @var \League\OAuth2\Server\Repositories\ClientRepositoryInterface
   */
  protected $clientRepository;

  /**
   * @var \League\OAuth2\Server\Repositories\ScopeRepositoryInterface
   */
  protected $scopeRepository;

  /**
   * @var \League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface
   */
  protected $accessTokenRepository;

  /**
   * @var \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface
   */
  protected $refreshTokenRepository;

  /**
   * @var \League\OAuth2\Server\ResponseTypes\ResponseTypeInterface
   */
  protected $responseType;

  /**
   * @var string
   */
  protected $privateKeyPath;

  /**
   * @var string
   */
  protected $publicKeyPath;

  /**
   * @var \DateTime
   */
  protected $expiration;

  /**
   * Constructor for Oauth2GrantManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \League\OAuth2\Server\Repositories\ClientRepositoryInterface $client_repository
   *   The client repository.
   * @param \League\OAuth2\Server\Repositories\ScopeRepositoryInterface $scope_repository
   *   The scope repository.
   * @param \League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface $access_token_repository
   *   The access token repository.
   * @param \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface $refresh_token_repository
   *   The refresh token repository.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \League\OAuth2\Server\ResponseTypes\ResponseTypeInterface $response_type
   *   The authorization server response type.
   *
   * @throws \Exception
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    ClientRepositoryInterface $client_repository,
    ScopeRepositoryInterface $scope_repository,
    AccessTokenRepositoryInterface $access_token_repository,
    RefreshTokenRepositoryInterface $refresh_token_repository,
    ConfigFactoryInterface $config_factory,
    ResponseTypeInterface $response_type = NULL
  ) {
    parent::__construct('Plugin/Oauth2Grant', $namespaces, $module_handler, 'Drupal\simple_oauth\Plugin\Oauth2GrantInterface', 'Drupal\simple_oauth\Annotation\Oauth2Grant');

    $this->alterInfo('simple_oauth_oauth2_grant_info');
    $this->setCacheBackend($cache_backend, 'simple_oauth_oauth2_grant_plugins');

    $this->clientRepository = $client_repository;
    $this->scopeRepository = $scope_repository;
    $this->accessTokenRepository = $access_token_repository;
    $this->refreshTokenRepository = $refresh_token_repository;
    $this->responseType = $response_type;
    $settings = $config_factory->get('simple_oauth.settings');
    $this->setKeyPaths($settings);
    $this->expiration = new \DateInterval(sprintf('PT%dS', $settings->get('access_token_expiration')));
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationServer($grant_type) {
    try {
      /** @var \Drupal\simple_oauth\Plugin\Oauth2GrantInterface $plugin */
      $plugin = $this->createInstance($grant_type);
    }
    catch (PluginNotFoundException $exception) {
      throw OAuthServerException::invalidGrant('Check the configuration to see if the grant is enabled.');
    }

    $this->checkKeyPaths();
    $salt = Settings::getHashSalt();

    // The hash salt must be at least 32 characters long.
    if (Core::ourStrlen($salt) < 32) {
      throw OAuthServerException::serverError('Hash salt must be at least 32 characters long.');
    }

    $server = new AuthorizationServer(
      $this->clientRepository,
      $this->accessTokenRepository,
      $this->scopeRepository,
      realpath($this->privateKeyPath),
      Core::ourSubstr($salt, 0, 32),
      $this->responseType
    );
    // Enable the password grant on the server with a token TTL of X hours.
    $server->enableGrantType(
      $plugin->getGrantType(),
      $this->expiration
    );

    return $server;
  }

  /**
   * Set the public and private key paths.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   The Simple OAuth settings configuration object.
   */
  protected function setKeyPaths(ImmutableConfig $settings) {
    $this->publicKeyPath = $settings->get('public_key');
    $this->privateKeyPath = $settings->get('private_key');
  }

  /**
   * @throws \League\OAuth2\Server\Exception\OAuthServerException
   *   If one or both keys are not set properly.
   */
  protected function checkKeyPaths() {
    if (!file_exists($this->publicKeyPath) || !file_exists($this->privateKeyPath)) {
      throw OAuthServerException::serverError(sprintf('You need to set the OAuth2 secret and private keys.'));
    }
  }

}
