<?php

namespace Drupal\Tests\simple_oauth_extras\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\consumers\Entity\Consumer;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\simple_oauth\Functional\RequestHelperTrait;
use Drupal\user\Entity\Role;

/**
 * @group simple_oauth_extras
 */
class RolesNegotiationFunctionalTest extends BrowserTestBase {

  use RequestHelperTrait;

  public static $modules = [
    'image',
    'simple_oauth',
    'simple_oauth_extras',
    'text',
    'user',
  ];

  /**
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * @var \Drupal\Core\Url
   */
  protected $tokenTestUrl;

  /**
   * @var \Drupal\consumers\Entity\Consumer
   */
  protected $client;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;


  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

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
  protected $clientSecret;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->htmlOutputEnabled = FALSE;
    $this->tokenTestUrl = Url::fromRoute('oauth2_token.user_debug');
    $this->url = Url::fromRoute('oauth2_token.token');
    $this->user = $this->drupalCreateUser();
    // Set up a HTTP client that accepts relative URLs.
    $this->httpClient = $this->container->get('http_client_factory')
      ->fromOptions(['base_uri' => $this->baseUrl]);
    $this->clientSecret = $this->getRandomGenerator()->string();
    // Create a role 'foo' and add two permissions to it.
    $role = Role::create([
      'id' => 'foo',
      'label' => 'Foo',
      'is_admin' => FALSE,
    ]);
    $role->grantPermission('view own simple_oauth entities');
    $role->save();
    $role = Role::create([
      'id' => 'bar',
      'label' => 'Bar',
      'is_admin' => FALSE,
    ]);
    $role->grantPermission('administer simple_oauth entities');
    $role->save();
    $role = Role::create([
      'id' => 'oof',
      'label' => 'Oof',
      'is_admin' => FALSE,
    ]);
    $role->grantPermission('delete own simple_oauth entities');
    $role->save();
    $this->user->addRole('foo');
    $this->user->addRole('bar');
    $this->user->save();

    // Create a Consumer.
    $this->client = Consumer::create([
      'owner_id' => 1,
      'user_id' => $this->user->id(),
      'label' => $this->getRandomGenerator()->name(),
      'secret' => $this->clientSecret,
      'confidential' => TRUE,
      'roles' => [['target_id' => 'oof']],
    ]);
    $this->client->save();

    // Configure the public and private keys.
    $path = $this->container->get('module_handler')
      ->getModule('simple_oauth')
      ->getPath();
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
  }

  /**
   * Test access to own published node with missing role on User entity.
   */
  public function testRequestWithRoleRemovedFromUser() {
    $access_token = $this->getAccessToken(['foo', 'bar']);

    // Get detailed information about the authenticated user.
    $response = $this->request(
      'GET',
      $this->tokenTestUrl,
      [
        'query' => ['_format' => 'json'],
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]
    );
    $parsed_response = Json::decode($response->getBody()->getContents());
    $this->assertEquals($this->user->id(), $parsed_response['id']);
    $this->assertEquals(['foo', 'bar', 'authenticated', 'oof'], $parsed_response['roles']);
    $this->assertTrue($parsed_response['permissions']['view own simple_oauth entities']['access']);
    $this->assertTrue($parsed_response['permissions']['administer simple_oauth entities']['access']);

    $this->user->removeRole('bar');
    $this->user->save();

    // We have edited the user, but there was a non-expired existing token for
    // that user. Even though the TokenUser has the roles assigned, the
    // underlying user doesn't, so access should not be granted.
    $response = $this->request(
      'GET',
      $this->tokenTestUrl,
      [
        'query' => ['_format' => 'json'],
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]
    );
    $parsed_response = Json::decode($response->getBody()->getContents());
    // The token was successfully removed. The negotiated user is the anonymous
    // user.
    $this->assertEquals(0, $parsed_response['id']);
    $this->assertEquals(['anonymous'], $parsed_response['roles']);
    $this->assertFalse($parsed_response['permissions']['view own simple_oauth entities']['access']);
    $this->assertFalse($parsed_response['permissions']['administer simple_oauth entities']['access']);

    // Request the access token again. This time the user doesn't have the role
    // requested at the time of generating the token.
    $access_token = $this->getAccessToken(['foo', 'bar']);
    $response = $this->request(
      'GET',
      $this->tokenTestUrl,
      [
        'query' => ['_format' => 'json'],
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]
    );
    $parsed_response = Json::decode($response->getBody()->getContents());
    // The negotiated user is the expected user.
    $this->assertEquals($this->user->id(), $parsed_response['id']);
    $this->assertEquals(['foo', 'authenticated', 'oof'], $parsed_response['roles']);
    $this->assertTrue($parsed_response['permissions']['view own simple_oauth entities']['access']);
    $this->assertFalse($parsed_response['permissions']['administer simple_oauth entities']['access']);
  }

  /**
   * Test access to own unpublished node but with the role removed from client.
   */
  public function testRequestWithRoleRemovedFromClient() {
    $access_token = $this->getAccessToken(['oof']);

    // Get detailed information about the authenticated user.
    $response = $this->request(
      'GET',
      $this->tokenTestUrl,
      [
        'query' => ['_format' => 'json'],
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]
    );
    $parsed_response = Json::decode($response->getBody()->getContents());
    $this->assertEquals($this->user->id(), $parsed_response['id']);
    $this->assertEquals(['authenticated', 'oof'], $parsed_response['roles']);
    $this->assertTrue($parsed_response['permissions']['delete own simple_oauth entities']['access']);

    $this->client->set('roles', []);
    // After saving the client entity, the token should be deleted.
    $this->client->save();

    // User should NOT have access to view own simple_oauth entities,
    // because the scope is indicated in the token request, but
    // missing from the client content entity.
    $response = $this->request(
      'GET',
      $this->tokenTestUrl,
      [
        'query' => ['_format' => 'json'],
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]
    );
    $parsed_response = Json::decode($response->getBody()->getContents());
    // The token was successfully removed. The negotiated user is the anonymous
    // user.
    $this->assertEquals(0, $parsed_response['id']);
    $this->assertEquals(['anonymous'], $parsed_response['roles']);
    $this->assertFalse($parsed_response['permissions']['view own simple_oauth entities']['access']);

    $access_token = $this->getAccessToken(['oof']);
    // Get detailed information about the authenticated user.
    $response = $this->request(
      'GET',
      $this->tokenTestUrl,
      [
        'query' => ['_format' => 'json'],
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]
    );
    $parsed_response = Json::decode($response->getBody()->getContents());
    $this->assertEquals($this->user->id(), $parsed_response['id']);
    $this->assertEquals(['authenticated'], $parsed_response['roles']);
    $this->assertFalse($parsed_response['permissions']['delete own simple_oauth entities']['access']);
  }

  /**
   * Test access to own unpublished node but with missing scope.
   */
  public function testRequestWithMissingScope() {
    $access_token = $this->getAccessToken();

    $response = $this->request(
      'GET',
      $this->tokenTestUrl,
      [
        'query' => ['_format' => 'json'],
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
        ],
      ]
    );
    $parsed_response = Json::decode($response->getBody()->getContents());
    $this->assertEquals($this->user->id(), $parsed_response['id']);
    $this->assertEquals(['authenticated', 'oof'], $parsed_response['roles']);
    $this->assertFalse($parsed_response['permissions']['view own simple_oauth entities']['access']);
  }

  /**
   * Return an access token.
   *
   * @param array $scopes
   *   The scopes.
   *
   * @return string
   *   The access token.
   */
  private function getAccessToken(array $scopes = []) {
    $valid_payload = [
      'grant_type' => 'client_credentials',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
    ];
    if (!empty($scopes)) {
      $valid_payload['scope'] = implode(' ', $scopes);
    }
    $response = $this->request(
      'POST',
      $this->url,
      ['form_params' => $valid_payload]
    );
    $parsed_response = Json::decode($response->getBody()->getContents());

    return $parsed_response['access_token'];
  }

}
