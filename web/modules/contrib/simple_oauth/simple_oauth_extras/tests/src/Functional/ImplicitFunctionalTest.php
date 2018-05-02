<?php

namespace Drupal\Tests\simple_oauth_extras\Functional;

use Drupal\Core\Url;
use Drupal\Tests\simple_oauth\Functional\TokenBearerFunctionalTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * @group simple_oauth_extras
 */
class ImplicitFunctionalTest extends TokenBearerFunctionalTestBase {

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
    $this->client->save();
    $this->authorizeUrl = Url::fromRoute('oauth2_token_extras.authorize');
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), [
      'grant simple_oauth codes',
    ]);
  }

  /**
   * Test the valid Implicit grant.
   */
  public function testImplicitGrant() {
    $valid_params = [
      'response_type' => 'token',
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
    $assert_session->statusCodeEquals(500);
    $this
      ->config('simple_oauth_extras.settings')
      ->set('use_implicit', TRUE)
      ->save();
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
    $assert_session->statusCodeEquals(200);
    $assert_session->addressMatches('/\/oauth\/test#access_token=.*&token_type=Bearer&expires_in=\d*/');
  }

}
