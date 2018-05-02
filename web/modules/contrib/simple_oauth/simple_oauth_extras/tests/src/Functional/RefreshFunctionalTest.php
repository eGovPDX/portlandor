<?php

namespace Drupal\Tests\simple_oauth_extras\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\simple_oauth\Functional\TokenBearerFunctionalTestBase;
use League\OAuth2\Server\CryptTrait;

/**
 * @group simple_oauth_extras
 */
class RefreshFunctionalTest extends TokenBearerFunctionalTestBase {

  use CryptTrait;

  public static $modules = [
    'simple_oauth_extras',
  ];
  /**
   * @var string
   */
  protected $refreshToken;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->scope = 'authenticated';
    $valid_payload = [
      'grant_type' => 'password',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'username' => $this->user->getAccountName(),
      'password' => $this->user->pass_raw,
      'scope' => $this->scope,
    ];
    $response = $this->request('POST', $this->url, [
      'form_params' => $valid_payload,
    ]);
    $body = $response->getBody()->getContents();
    $parsed_response = Json::decode($body);
    $this->refreshToken = $parsed_response['refresh_token'];
  }

  /**
   * Test the valid Refresh grant.
   */
  public function testRefreshGrant() {
    // 1. Test the valid response.
    $valid_payload = [
      'grant_type' => 'refresh_token',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'refresh_token' => $this->refreshToken,
      'scope' => $this->scope,
    ];
    $response = $this->request('POST', $this->url, [
      'form_params' => $valid_payload,
    ]);
    $this->assertValidTokenResponse($response, TRUE);

    // 2. Test the valid without scopes.
    // We need to use the new refresh token, the old one is revoked.
    $response->getBody()->rewind();
    $parsed_response = Json::decode($response->getBody()->getContents());
    $valid_payload = [
      'grant_type' => 'refresh_token',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'refresh_token' => $parsed_response['refresh_token'],
      'scope' => $this->scope,
    ];
    $response = $this->request('POST', $this->url, [
      'form_params' => $valid_payload,
    ]);
    $this->assertValidTokenResponse($response, TRUE);

    // 3. Test that the token token was revoked.
    $valid_payload = [
      'grant_type' => 'refresh_token',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'refresh_token' => $this->refreshToken,
    ];
    $response = $this->request('POST', $this->url, [
      'form_params' => $valid_payload,
    ]);
    $parsed_response = Json::decode($response->getBody()->getContents());
    $this->assertSame(401, $response->getStatusCode());
    $this->assertSame('invalid_request', $parsed_response['error']);
  }

  /**
   * Test invalid Refresh grant.
   */
  public function testMissingRefreshGrant() {
    $valid_payload = [
      'grant_type' => 'refresh_token',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'refresh_token' => $this->refreshToken,
      'scope' => $this->scope,
    ];

    $data = [
      'grant_type' => [
        'error' => 'invalid_grant',
        'code' => 400,
      ],
      'client_id' => [
        'error' => 'invalid_request',
        'code' => 400,
      ],
      'client_secret' => [
        'error' => 'invalid_client',
        'code' => 401,
      ],
      'refresh_token' => [
        'error' => 'invalid_request',
        'code' => 400,
      ],
    ];
    foreach ($data as $key => $value) {
      $invalid_payload = $valid_payload;
      unset($invalid_payload[$key]);
      $response = $this->request('POST', $this->url, [
        'form_params' => $invalid_payload,
      ]);
      $parsed_response = Json::decode($response->getBody()->getContents());
      $this->assertSame($value['error'], $parsed_response['error'], sprintf('Correct error code %s for %s.', $value['error'], $key));
      $this->assertSame($value['code'], $response->getStatusCode(), sprintf('Correct status code %d for %s.', $value['code'], $key));
    }
  }

  /**
   * Test invalid Refresh grant.
   */
  public function testInvalidRefreshGrant() {
    $valid_payload = [
      'grant_type' => 'refresh_token',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'refresh_token' => $this->refreshToken,
      'scope' => $this->scope,
    ];

    $data = [
      'grant_type' => [
        'error' => 'invalid_grant',
        'code' => 400,
      ],
      'client_id' => [
        'error' => 'invalid_client',
        'code' => 401,
      ],
      'client_secret' => [
        'error' => 'invalid_client',
        'code' => 401,
      ],
      'refresh_token' => [
        'error' => 'invalid_request',
        'code' => 401,
      ],
    ];
    foreach ($data as $key => $value) {
      $invalid_payload = $valid_payload;
      $invalid_payload[$key] = $this->getRandomGenerator()->string();
      $response = $this->request('POST', $this->url, [
        'form_params' => $invalid_payload,
      ]);
      $parsed_response = Json::decode($response->getBody()->getContents());
      $this->assertSame($value['error'], $parsed_response['error'], sprintf('Correct error code %s for %s.', $value['error'], $key));
      $this->assertSame($value['code'], $response->getStatusCode(), sprintf('Correct status code %d for %s.', $value['code'], $key));
    }
  }

}
