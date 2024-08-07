<?php

namespace Drupal\portland_openid_connect\Plugin\OpenIDConnectClient;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Drupal\openid_connect\StateToken;
use Drupal\portland_openid_connect\SecretsReader;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 *
 * @OpenIDConnectClient(
 *   id = "azure",
 *   label = @Translation("City of Portland network account")
 * )
 */
class Azure extends OpenIDConnectClientBase {
  /**
   * The secrets reader.
   *
   * @var \Drupal\portland_openid_connect\SecretsReader
   */
  protected $secretsReader;

  /**
   * The constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $request_stack,
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    SecretsReader $secrets_reader
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $request_stack,
      $http_client,
      $logger_factory
    );

    $this->secretsReader = $secrets_reader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
      ContainerInterface $container,
      array $configuration,
      $plugin_id,
      $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('portland_openid_connect.secrets_reader')
    );
  }

  /**
   * Overrides OpenIDConnectClientBase::buildConfigurationForm().
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    // Ensure that the client_id and client_secret values are blank because they will be retrieved from 
    // a secrets file... but we can display those values to the user to remind them that they're being 
    // used internally in case they ever need to modify them.
    $form['client_id'] = [
      '#type' => 'hidden',
      '#default_value' => '',
    ];
    $form['client_secret'] = [
      '#type' => 'hidden',
      '#default_value' => '',
    ];
    $form['client_id_display'] = [
      '#title' => $this->t('Client ID'),
      '#type' => 'item',
      '#markup' => $this->secretsReader->get('azure_client_id') . ' (value of \'azure_client_id\' in secrets.json)',
    ];
    $form['client_secret_display'] = [
      '#title' => $this->t('Client secret'),
      '#type' => 'item',
      '#markup' => $this->secretsReader->get('azure_client_secret') . ' (value of \'azure_client_secret\' in secrets.json)',
    ];
    
    $form['authorization_endpoint'] = [
      '#title' => $this->t('Authorization endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['authorization_endpoint'],
    ];
    $form['token_endpoint'] = [
      '#title' => $this->t('Token endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['token_endpoint'],
    ];
    $form['userinfo_endpoint'] = [
      '#title' => $this->t('Userinfo endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['userinfo_endpoint'],
    ];

    return $form;
  }

  /**
   * Overrides OpenIDConnectClientBase::getEndpoints().
   */
  public function getEndpoints() {
    return [
      'authorization' => $this->configuration['authorization_endpoint'],
      'token' => $this->configuration['token_endpoint'],
      'userinfo' => $this->configuration['userinfo_endpoint'],
    ];
  }

  /**
   * Implements OpenIDConnectClientInterface::authorize().
   *
   * @param string $scope
   *   A string of scopes.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A trusted redirect response object.
   */
  public function authorize($scope = 'openid email') {
    $language_none = \Drupal::languageManager()
      ->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE);
    $redirect_uri = Url::fromRoute(
      'openid_connect.redirect_controller_redirect',
      [
        'client_name' => $this->pluginId,
      ],
      [
        'absolute' => TRUE,
        'language' => $language_none,
      ]
    )->toString(TRUE);

    $url_options = [
      'query' => [
        'client_id' => $this->secretsReader->get('azure_client_id'),
        'response_type' => 'code',
        'scope' => $scope,
        'redirect_uri' => $redirect_uri->getGeneratedUrl(),
        'state' => StateToken::create(),
      ],
    ];

    $endpoints = $this->getEndpoints();
    // Clear _GET['destination'] because we need to override it.
    $this->requestStack->getCurrentRequest()->query->remove('destination');
    $authorization_endpoint = Url::fromUri($endpoints['authorization'], $url_options)->toString(TRUE);

    $response = new TrustedRedirectResponse($authorization_endpoint->getGeneratedUrl());
    // We can't cache the response, since this will prevent the state to be
    // added to the session. The kill switch will prevent the page getting
    // cached for anonymous users when page cache is active.
    \Drupal::service('page_cache_kill_switch')->trigger();

    return $response;
  }

  /**
   * Implements OpenIDConnectClientInterface::retrieveIDToken().
   *
   * @param string $authorization_code
   *   A authorization code string.
   *
   * @return array|bool
   *   A result array or false.
   */
  public function retrieveTokens($authorization_code) {
    // Exchange `code` for access token and ID token.
    $redirect_uri = Url::fromRoute(
      'openid_connect.redirect_controller_redirect',
      ['client_name' => $this->pluginId], ['absolute' => TRUE]
    )->toString();
    $endpoints = $this->getEndpoints();

    $request_options = [
      'form_params' => [
        'code' => $authorization_code,
        'client_id' => $this->secretsReader->get('azure_client_id'),
        'client_secret' => $this->secretsReader->get('azure_client_secret'),
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code',
        'resource' => 'https://graph.windows.net', // to access user profile
      ],
    ];

    /* @var \GuzzleHttp\ClientInterface $client */
    $client = $this->httpClient;

    try {
      $response = $client->post($endpoints['token'], $request_options);
      $response_data = json_decode((string) $response->getBody(), TRUE);

      // Expected result.
      $tokens = [
        'id_token' => $response_data['id_token'],
        'access_token' => $response_data['access_token'],
      ];
      if (array_key_exists('expires_in', $response_data)) {
        $tokens['expire'] = \Drupal::time()->getRequestTime() + $response_data['expires_in'];
      }
      return $tokens;
    }
    catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not retrieve tokens',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('portland OpenID')
        ->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
  }

  /**
   * Implements OpenIDConnectClientInterface::retrieveUserInfo().
   *
   * @param string $access_token
   *   An access token string.
   *
   * @return array|bool
   *   A result array or false.
   */
  public function retrieveUserInfo($access_token) {
    $firstname_property = 'givenName';
    $lastname_property = 'surname';
    $primary_email_property = 'mail';
    $backup_mail_property = 'userPrincipalName';

    $url = $this->getEndpoints()['userinfo'];

    // Perform the request.
    $options = [
      'method' => 'GET',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
      ],
    ];
    $client = $this->httpClient;

    try {
      $response = $client->get($url, $options);
      $response_data = (string) $response->getBody();

      // Profile Information
      $profile_data = json_decode($response_data, TRUE);
      $profile_data['name'] = $profile_data[$firstname_property] . ' ' . $profile_data[$lastname_property];
      $profile_data['email'] = $profile_data[$primary_email_property];

      if (!isset($profile_data['email'])) {
        // Write watchdog warning
        $variables = ['@user' => $profile_data[$backup_mail_property]];

        $this->loggerFactory->get('portland OpenID')
          ->warning('Email address of user @user not found in UserInfo. Used backup email instead, please check.', $variables);

        $profile_data['email'] = $profile_data[$backup_mail_property];
      }

      return $profile_data;
    }
    catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not retrieve user profile information',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('portland OpenID')
        ->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
    return $userinfo;
  }
}