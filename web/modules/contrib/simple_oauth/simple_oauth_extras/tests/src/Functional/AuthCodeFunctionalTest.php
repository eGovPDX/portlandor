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

  /**
   * @var \Drupal\user\RoleInterface
   */
  protected $extraRole;

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
    // Add a scope so we can ensure all tests have at least 2 roles. That way we
    // can test dropping a scope and still have at least one scope.
    $additional_scope = $this->getRandomGenerator()->name(8, TRUE);
    Role::create([
      'id' => $additional_scope,
      'label' => $this->getRandomGenerator()->word(5),
      'is_admin' => FALSE,
    ])->save();
    $this->scope = $this->scope . ' ' . $additional_scope;
    // Add a random scope that is not in the base scopes list to request so we
    // can make extra checks on it.
    $this->extraRole = Role::create([
      'id' => $this->getRandomGenerator()->name(8, TRUE),
      'label' => $this->getRandomGenerator()->word(5),
      'is_admin' => FALSE,
    ]);
    $this->extraRole->save();
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
    $this->assertGrantForm();

    // 3. Grant access by submitting the form and get the token back.
    $this->drupalPostForm($this->authorizeUrl, [], 'Grant', [
      'query' => $valid_params,
    ]);
    // Store the code for the second part of the flow.
    $code = $this->getAndValidateCodeFromResponse();

    // 4. Send the code to get the access token.
    $response = $this->postGrantedCodeWithScopes($code, $this->scope);
    $this->assertValidTokenResponse($response, TRUE);
  }

  /**
   * Test the valid AuthCode grant if the client is non 3rd party.
   */
  public function testNon3rdPartyClientAuthCodeGrant() {
    $this->client->set('third_party', FALSE);
    $this->client->save();

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

    // 2. Log the user in and try again. This time we should get a code
    // immediately without granting, because the consumer is not 3rd party.
    $this->drupalLogin($this->user);
    $this->drupalGet($this->authorizeUrl->toString(), [
      'query' => $valid_params,
    ]);
    // Store the code for the second part of the flow.
    $code = $this->getAndValidateCodeFromResponse();

    // 3. Send the code to get the access token, regardless of the scopes, since
    // the consumer is trusted.
    $response = $this->postGrantedCodeWithScopes(
      $code,
      $this->scope . ' ' . $this->extraRole->id()
    );
    $this->assertValidTokenResponse($response, TRUE);
  }


  /**
   * Helper function to assert the current page is a valid grant form.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertGrantForm() {
    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals(200);
    $assert_session->titleEquals('Grant Access to Client | Drupal');
    $assert_session->buttonExists('Grant');
    $assert_session->responseContains('Permissions');
  }

  /**
   * Get the code in the response after granting access to scopes.
   *
   * @return mixed
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function getAndValidateCodeFromResponse() {
    $assert_session = $this->assertSession();
    $session = $this->getSession();
    $assert_session->statusCodeEquals(200);
    $parsed_url = parse_url($session->getCurrentUrl());
    $parsed_query = \GuzzleHttp\Psr7\parse_query($parsed_url['query']);
    $this->assertArrayHasKey('code', $parsed_query);
    return $parsed_query['code'];
  }

  /**
   * Posts the code and requests access to the scopes.
   *
   * @param string $code
   *   The granted code.
   * @param string $scopes
   *   The list of scopes to request access to.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   */
  protected function postGrantedCodeWithScopes($code, $scopes) {
    $valid_payload = [
      'grant_type' => 'authorization_code',
      'client_id' => $this->client->uuid(),
      'client_secret' => $this->clientSecret,
      'code' => $code,
      'scope' => $scopes,
    ];
    return $this->request('POST', $this->url, [
      'form_params' => $valid_payload,
    ]);
  }

}
