<?php

namespace Drupal\Tests\simple_oauth\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\consumers\Entity\Consumer;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TokenBearerFunctionalTestBase.
 *
 * Base class that handles common logic and config for the token tests.
 *
 * @package Drupal\Tests\simple_oauth\Functional
 */
abstract class TokenBearerFunctionalTestBase extends BrowserTestBase {

  use RequestHelperTrait;

  public static $modules = [
    'image',
    'node',
    'serialization',
    'simple_oauth',
    'text',
  ];

  /**
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * @var \Drupal\consumers\Entity\Consumer
   */
  protected $client;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * @var string
   */
  protected $clientSecret;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Drupal\user\RoleInterface[]
   */
  protected $additionalRoles;

  /**
   * @var string
   */
  protected $privateKeyPath;

  /**
   * @var string
   */
  protected $publicKeyPath;

  /**
   * @var string
   */
  protected $scope;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->htmlOutputEnabled = FALSE;

    $this->url = Url::fromRoute('oauth2_token.token');

    // Set up a HTTP client that accepts relative URLs.
    $this->httpClient = $this->container->get('http_client_factory')
      ->fromOptions(['base_uri' => $this->baseUrl]);

    $client_role = Role::create([
      'id' => $this->getRandomGenerator()->name(8, TRUE),
      'label' => $this->getRandomGenerator()->word(5),
      'is_admin' => FALSE,
    ]);
    $client_role->save();

    $this->additionalRoles = [];
    for ($i = 0; $i < mt_rand(1, 3); $i++) {
      $role = Role::create([
        'id' => $this->getRandomGenerator()->name(8, TRUE),
        'label' => $this->getRandomGenerator()->word(5),
        'is_admin' => FALSE,
      ]);
      $role->save();
      $this->additionalRoles[] = $role;
    }

    $this->clientSecret = $this->getRandomGenerator()->string();

    $this->client = Consumer::create([
      'owner_id' => '',
      'label' => $this->getRandomGenerator()->name(),
      'secret' => $this->clientSecret,
      'confidential' => TRUE,
      'roles' => [['target_id' => $client_role->id()]],
    ]);
    $this->client->save();

    $this->user = $this->drupalCreateUser();
    $this->grantPermissions(Role::load(RoleInterface::ANONYMOUS_ID), [
      'access content',
    ]);
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), [
      'access content',
    ]);

    // Use the public and private keys.
    $path = $this->container->get('module_handler')->getModule('simple_oauth')->getPath();
    $temp_dir = sys_get_temp_dir();
    $public_path = '/' . $path . '/tests/certificates/public.key';
    $private_path = '/' . $path . '/tests/certificates/private.key';
    file_put_contents($temp_dir . '/public.key', file_get_contents(DRUPAL_ROOT . $public_path));
    file_put_contents($temp_dir . '/private.key', file_get_contents(DRUPAL_ROOT . $private_path));
    chmod($temp_dir . '/public.key', 0660);
    chmod($temp_dir . '/private.key', 0660);
    $this->publicKeyPath = $temp_dir . '/public.key';
    $this->privateKeyPath = $temp_dir . '/private.key';
    $settings = $this->config('simple_oauth.settings');
    $settings->set('public_key', $this->publicKeyPath);
    $settings->set('private_key', $this->privateKeyPath);
    $settings->save();

    $num_roles = mt_rand(1, count($this->additionalRoles));
    $requested_roles = array_slice($this->additionalRoles, 0, $num_roles);
    $scopes = array_map(function (RoleInterface $role) {
      return $role->id();
    }, $requested_roles);
    $this->scope = implode(' ', $scopes);

    drupal_flush_all_caches();
  }

  /**
   * Validates a valid token response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param bool $has_refresh
   *   TRUE if the response should return a refresh token. FALSE otherwise.
   */
  protected function assertValidTokenResponse(ResponseInterface $response, $has_refresh = FALSE) {
    $this->assertEquals(200, $response->getStatusCode());
    $parsed_response = Json::decode($response->getBody()->getContents());
    $this->assertSame('Bearer', $parsed_response['token_type']);
    $expiration = $this->config('simple_oauth.settings')->get('access_token_expiration');
    $this->assertLessThanOrEqual($expiration, $parsed_response['expires_in']);
    $this->assertGreaterThanOrEqual($expiration - 10, $parsed_response['expires_in']);
    $this->assertNotEmpty($parsed_response['access_token']);
    if ($has_refresh) {
      $this->assertNotEmpty($parsed_response['refresh_token']);
    }
    else {
      $this->assertTrue(empty($parsed_response['refresh_token']));
    }
  }

}
