<?php

namespace Drupal\simple_oauth_extras\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\simple_oauth\Entities\UserEntity;
use Drupal\simple_oauth\Plugin\Oauth2GrantManagerInterface;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Oauth2AuthorizeForm extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface
   */
  protected $messageFactory;

  /**
   * @var \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface
   */
  protected $foundationFactory;

  /**
   * @var \League\OAuth2\Server\AuthorizationServer
   */
  protected $server;

  /**
   * @var \Drupal\simple_oauth\Plugin\Oauth2GrantManagerInterface
   */
  protected $grantManager;

  /**
   * Oauth2AuthorizeForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface $message_factory
   * @param \Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface $foundation_factory
   * @param \Drupal\simple_oauth\Plugin\Oauth2GrantManagerInterface $grant_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, HttpMessageFactoryInterface $message_factory, HttpFoundationFactoryInterface $foundation_factory, Oauth2GrantManagerInterface $grant_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messageFactory = $message_factory;
    $this->foundationFactory = $foundation_factory;
    $this->grantManager = $grant_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('psr7.http_message_factory'),
      $container->get('psr7.http_foundation_factory'),
      $container->get('plugin.manager.oauth2_grant.processor')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'simple_oauth_authorize_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->currentUser()->isAuthenticated()) {
      $form['redirect_params'] = ['#type' => 'hidden', '#value' => $this->getRequest()->getQueryString()];
      $form['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('An external client application is requesting access to your data in this site. Please log in first to authorize the operation.'),
      ];
      $form['submit'] = ['#type' => 'submit', '#value' => $this->t('Login')];
      return $form;
    }
    $request = $this->getRequest();
    if ($request->get('response_type') == 'code') {
      $grant_type = 'code';
    }
    elseif ($request->get('response_type') == 'token') {
      $grant_type = 'implicit';
    }
    else {
      $grant_type = NULL;
    }
    $this->server = $this
      ->grantManager
      ->getAuthorizationServer($grant_type);

    // Transform the HTTP foundation request object into a PSR-7 object. The
    // OAuth library expects a PSR-7 request.
    $psr7_request = $this->messageFactory->createRequest($request);
    // Validate the HTTP request and return an AuthorizationRequest object.
    // The auth request object can be serialized into a user's session.
    $auth_request = $this->server->validateAuthorizationRequest($psr7_request);

    // Store the auth request temporarily.
    $form_state->set('auth_request', $auth_request);

    $manager = $this->entityTypeManager;
    $form = [
      '#type' => 'container',
    ];

    $client_uuid = $request->get('client_id');
    $client_drupal_entities = $manager->getStorage('consumer')->loadByProperties([
      'uuid' => $client_uuid,
    ]);
    if (empty($client_drupal_entities)) {
      throw OAuthServerException::invalidClient();
    }
    $client_drupal_entity = reset($client_drupal_entities);

    // Gather all the role ids.
    $scope_ids = array_merge(
      explode(' ', $request->get('scope')),
      array_map(function ($item) {
        return $item['target_id'];
      }, $client_drupal_entity->get('roles')->getValue())
    );
    $user_roles = $manager->getStorage('user_role')->loadMultiple($scope_ids);
    $form['client'] = $manager->getViewBuilder('consumer')->view($client_drupal_entity);
    $client_drupal_entity->addCacheableDependency($form['client']);
    $form['scopes'] = [
      '#title' => $this->t('Permissions'),
      '#theme' => 'item_list',
      '#items' => [],
    ];
    foreach ($user_roles as $user_role) {
      $user_role->addCacheableDependency($form['scopes']);
      $form['scopes']['#items'][] = $user_role->label();
    }

    $form['redirect_uri'] = [
      '#type' => 'hidden',
      '#value' => $request->get('redirect_uri') ?
      $request->get('redirect_uri') :
      $client_drupal_entity->get('redirect')->value,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Grant'),
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($auth_request = $form_state->get('auth_request')) {
      // Once the user has logged in set the user on the AuthorizationRequest.
      $user_entity = new UserEntity();
      $user_entity->setIdentifier($this->currentUser()->id());
      $auth_request->setUser($user_entity);
      // Once the user has approved or denied the client update the status
      // (true = approved, false = denied).
      $can_grant_codes = $this->currentUser()->hasPermission('grant simple_oauth codes');
      $auth_request->setAuthorizationApproved((bool) $form_state->getValue('submit') && $can_grant_codes);
      // Return the HTTP redirect response.
      $response = $this->server->completeAuthorizationRequest($auth_request, new Response());
      // Get the location and return a secure redirect response.
      $redirect_response = TrustedRedirectResponse::create(
        $response->getHeaderLine('location'),
        $response->getStatusCode(),
        $response->getHeaders()
      );
      $form_state->setResponse($redirect_response);
    }
    elseif ($params = $form_state->getValue('redirect_params')) {
      $url = Url::fromRoute('user.login');
      $destination = Url::fromRoute('oauth2_token_extras.authorize', [], [
        'query' => UrlHelper::parse('/?' . $params)['query'],
      ]);
      $url->setOption('query', [
        'destination' => $destination->toString(),
      ]);
      $form_state->setRedirectUrl($url);
    }
  }

}
