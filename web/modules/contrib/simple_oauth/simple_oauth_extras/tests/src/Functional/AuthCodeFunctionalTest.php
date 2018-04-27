<?php

namespace Drupal\Tests\simple_oauth_extras\Functional;

use Drupal\Core\Url;
use Drupal\Tests\simple_oauth\Functional\TokenBearerFunctionalTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * @group simple_oauth_extras
 */
class AuthCodeFunctionalTest extends TokenBearerFunctionalTestBase {

  /**
   * @var \Drupal\Core\Url
   */
  protected $authorizeUrl;

  /**
   * @var string
   */
  protected $redirectUri;

  public static $modules = [
    'simple_oauth_extras',
    'simple_oauth_extras_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->redirectUri = Url::fromRoute('oauth2_token_extras.test_token', [], [
      'absolute' => TRUE,
    ])->toString();
    $this->client->set('redirect', $this->redirectUri);
    $this->client->set('description', $this->getRandomGenerator()->paragraphs());
    $this->client->save();
    $this->authorizeUrl = Url::fromRoute('oauth2_token_extras.authorize');
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), [
      'grant simple_oauth codes',
    ]);
  }

  /**
   * Test the valid AuthCode grant.
   */
  public function testAuthCodeGrant() {
    $valid_params = [
      'response_type' => 'code',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
    ];
    // 1. Anonymous request invites the user to log in.
    $this->drupalGet($this->authorizeUrl->toString(), [
      'query' => $valid_params,
    ]);
    $assert_session = $this->assertSession();
    $assert_session->buttonExists(t('Login'));
    $assert_session->responseContains(t('An external client application is requesting access'));

    // 2. Log the user in and try again.
    $this->drupalLogin($this->user);
    $this->drupalGet($this->authorizeUrl->toString(), [
      'query' => $valid_params,
    ]);
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->titleEquals('Grant Access to Client | Drupal');
    $assert_session->buttonExists('Grant');
    $assert_session->responseContains('Permissions');

    // 3. Grant access by submitting the form and get the token back.
    $this->drupalPostForm($this->authorizeUrl, [], 'Grant', [
      'query' => $valid_params,
    ]);
    $assert_session = $this->assertSession();
    $session = $this->getSession();
    $assert_session->statusCodeEquals(200);
    $parsed_url = parse_url($session->getCurrentUrl());
    $parsed_query = \GuzzleHttp\Psr7\parse_query($parsed_url['query']);
    $this->assertArrayHasKey('code', $parsed_query);
    // Store the code for the second part of the flow.
    $code = $parsed_query['code'];

    // 4. Send the code to get the access token.
    $valid_payload = [
      'grant_type' => 'authorization_code',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'code' => $code,
      'scope' => $this->scope,
    ];
    $response = $this->request('POST', $this->url, [
      'form_params' => $valid_payload,
    ]);
    $this->assertValidTokenResponse($response, TRUE);
  }

}
