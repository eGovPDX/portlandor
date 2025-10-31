<?php
namespace Drupal\portland_govdelivery\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\portland_govdelivery\Service\TopicsProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Portland GovDelivery.
 */
class PortlandGovDeliverySettingsForm extends ConfigFormBase {
  protected KeyRepositoryInterface $keyRepository;
  protected TopicsProvider $topicsProvider;

  public function __construct(KeyRepositoryInterface $key_repository, TopicsProvider $topics_provider) {
    $this->keyRepository = $key_repository;
    $this->topicsProvider = $topics_provider;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('key.repository'),
      $container->get('portland_govdelivery.topics_provider'),
    );
  }

  public function getFormId() {
    return 'portland_govdelivery_settings_form';
  }

  protected function getEditableConfigNames() {
    return ['portland_govdelivery.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('portland_govdelivery.settings');

    $form['api_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API base URL'),
      '#default_value' => $config->get('api_base_url') ?? 'https://stage-api.govdelivery.com/api/account/',
      '#description' => $this->t('The base API URL for GovDelivery (e.g., https://api.govdelivery.com/api/account/ for production or https://stage-api.govdelivery.com/api/account/ for staging). The account code will be appended to this. <strong>This field is read-only and must be changed in the configuration file.</strong>'),
      '#disabled' => TRUE,
    ];

    $form['account_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GovDelivery account code'),
      '#default_value' => $config->get('account_code') ?? 'ORPORTLAND_ENT',
      '#description' => $this->t('The GovDelivery account code (appended to the API base URL). <strong>This field is read-only and must be changed in the configuration file.</strong>'),
      '#required' => TRUE,
      '#disabled' => TRUE,
    ];

    // Provide a select of available keys stored in file storage.
    $key_options = $this->keyRepository->getKeyNamesAsOptions(['storage_method' => 'file']);
    if (empty($key_options)) {
      $key_options = ['govdelivery_username' => $this->t('govdelivery_username'), 'govdelivery_password' => $this->t('govdelivery_password')];
    }

    $form['username_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Username key'),
      '#options' => $key_options,
      '#default_value' => $config->get('username_key') ?? 'govdelivery_username',
      '#description' => $this->t('Select the Key ID that stores the GovDelivery username (the key value remains in the Keys module).'),
    ];

    $form['password_key'] = [
      '#type' => 'select',
      '#title' => $this->t('Password key'),
      '#options' => $key_options,
      '#default_value' => $config->get('password_key') ?? 'govdelivery_password',
      '#description' => $this->t('Select the Key ID that stores the GovDelivery password.'),
    ];

    // Connection status section after credentials.
    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('GovDelivery status'),
      '#open' => FALSE,
    ];

    try {
      $client = \Drupal::service('portland_govdelivery.client');
      $endpoint = $this->getResolvedEndpoint($client);
      $form['status']['endpoint'] = [
        '#type' => 'item',
        '#title' => $this->t('Resolved API base'),
        '#markup' => '<code>' . htmlspecialchars($endpoint) . '</code>',
      ];

      // Test connection by attempting to fetch topics.
      $test_result = $this->testConnection($client, $endpoint);
      $form['status']['test'] = [
        '#type' => 'item',
        '#title' => $this->t('Connection test'),
        '#markup' => $test_result,
      ];

      // Instance stats removed per request.
    }
    catch (\RuntimeException $e) {
      $form['status']['error'] = [
        '#type' => 'item',
        '#title' => $this->t('Configuration Error'),
        '#markup' => '<strong style="color:red;">✗ ' . htmlspecialchars($e->getMessage()) . '</strong>',
      ];
    }
    catch (\Throwable $e) {
      $form['status']['error'] = [
        '#type' => 'item',
        '#title' => $this->t('Status'),
        '#markup' => '<strong style="color:red;">' . $this->t('Error: @msg', ['@msg' => $e->getMessage()]) . '</strong>',
      ];
    }

    // Read-only topics listing.
    $form['topics_list'] = [
      '#type' => 'details',
      '#title' => $this->t('GovDelivery topics (read-only)'),
      '#open' => FALSE,
    ];

    try {
      $options = $this->topicsProvider->getWebformOptions();
    }
    catch (\Throwable $e) {
      $options = [];
      $form['topics_list']['error'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Unable to load topics: @msg', ['@msg' => $e->getMessage()]),
      ];
    }

    if (!empty($options)) {
      $rows = [];
      foreach ($options as $code => $label) {
        $rows[] = ['data' => [$code, $label]];
      }
      $form['topics_list']['table'] = [
        '#type' => 'table',
        '#header' => [$this->t('Code'), $this->t('Label')],
        '#rows' => $rows,
        '#empty' => $this->t('No topics found.'),
        '#attributes' => [
          'style' => 'max-height: 24em; overflow-y: auto; display: block;',
        ],
      ];
    }
    else {
      $form['topics_list']['empty'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No topics found.'),
      ];
    }

    // Subscribe user panel.
    $form['subscribe'] = [
      '#type' => 'details',
      '#title' => $this->t('Subscribe a user'),
      '#open' => FALSE,
      // Ensure child values are grouped under 'subscribe' in form_state.
      '#tree' => TRUE,
    ];

    $form['subscribe']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
      '#description' => $this->t('Email address to subscribe.'),
    ];

    // Topics multiselect with Select2 enhancement if available.
    $topic_options = $options ?? [];
    $form['subscribe']['topics'] = [
      '#type' => 'select',
      '#title' => $this->t('Topics'),
      '#options' => $topic_options,
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#description' => $this->t('Select one or more topics to subscribe the user to.'),
      '#attributes' => [
        'class' => ['use-select2'],
      ],
    ];
    // Attempt to attach Select2 library gracefully; if missing, field works as normal select.
    $form['#attached']['library'][] = 'select2/select2';

    // Action button for subscription.
    $form['subscribe']['actions'] = ['#type' => 'actions'];
    $form['subscribe']['actions']['subscribe_user'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe user'),
      '#name' => 'subscribe_user',
      '#button_type' => 'primary',
      '#submit' => ['::submitSubscribe'],
      // Validate only subscribe sub-tree to avoid requiring config fields.
      '#limit_validation_errors' => [['subscribe']],
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If subscription button triggered, skip config save.
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#name']) && $trigger['#name'] === 'subscribe_user') {
      return;
    }
    $this->config('portland_govdelivery.settings')
      // Note: api_base_url is not saved from form (it's disabled/read-only)
      ->set('username_key', $form_state->getValue('username_key'))
      ->set('password_key', $form_state->getValue('password_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for the Subscribe user action.
   */
  public function submitSubscribe(array &$form, FormStateInterface $form_state): void {
    // Read values from the subscribe subtree (requires #tree = TRUE on parent).
    $email = trim((string) $form_state->getValue(['subscribe', 'email']));
    $topics = (array) $form_state->getValue(['subscribe', 'topics']);

    // Fallback to flat values if not present (defensive in case of form rebuilds).
    if ($email === '' && $form_state->hasValue('email')) {
      $email = trim((string) $form_state->getValue('email'));
    }
    if (empty($topics) && $form_state->hasValue('topics')) {
      $topics = (array) $form_state->getValue('topics');
    }

    if (empty($email) || empty($topics)) {
      $this->messenger()->addError($this->t('Please provide an email address and select at least one topic.'));
      return;
    }

    // Determine locale from the current user.
    $langcode = \Drupal::currentUser()->getPreferredLangcode() ?: \Drupal::languageManager()->getCurrentLanguage()->getId();

    try {
      /** @var \Drupal\portland_govdelivery\Service\GovDeliveryClient $client */
      $client = \Drupal::service('portland_govdelivery.client');
      $client->subscribeUser($email, $topics, $langcode);
      $this->messenger()->addStatus($this->t('Subscription created for %email.', ['%email' => $email]));
    }
    catch (\Throwable $e) {
      $this->messenger()->addError($this->t('Subscription failed: @msg', ['@msg' => $e->getMessage()]));
      \Drupal::logger('portland_govdelivery')->error('Subscribe user failed for %email: @msg', ['%email' => $email, '@msg' => $e->getMessage()]);
    }
  }

  /**
   * Helper to get the resolved API base from the client via reflection.
   */
  protected function getResolvedEndpoint($client): string {
    try {
      $reflection = new \ReflectionClass($client);
      $method = $reflection->getMethod('getAccountApiBase');
      $method->setAccessible(TRUE);
      return $method->invoke($client);
    }
    catch (\Throwable $e) {
      return 'Unable to resolve: ' . $e->getMessage();
    }
  }

  /**
   * Test connection and return a status message.
   */
  protected function testConnection($client, string $endpoint): string {
    try {
      $http_client = \Drupal::httpClient();
      $config = $this->config('portland_govdelivery.settings');
      $kr = $this->keyRepository;
      $username_key_id = $config->get('username_key') ?: 'govdelivery_username';
      $password_key_id = $config->get('password_key') ?: 'govdelivery_password';
      $username_key = $kr->getKey($username_key_id);
      $password_key = $kr->getKey($password_key_id);

      if (!$username_key || !$password_key) {
        return '<strong style="color:orange;">⚠ Keys not configured</strong>';
      }

      $username = $username_key->getKeyValue();
      $password = $password_key->getKeyValue();

      $response = $http_client->request('GET', $endpoint . '/topics', [
        'auth' => [$username, $password],
        'headers' => ['Accept' => 'application/xml'],
        'http_errors' => FALSE,
        'timeout' => 10,
      ]);

      $status = $response->getStatusCode();
      if ($status === 200) {
        return '<strong style="color:green;">✓ Connected (HTTP ' . $status . ')</strong>';
      }
      elseif ($status === 401) {
        return '<strong style="color:red;">✗ Unauthorized (HTTP 401) - Check credentials</strong>';
      }
      elseif ($status === 404) {
        return '<strong style="color:red;">✗ Not Found (HTTP 404) - Check account code/endpoint</strong>';
      }
      else {
        return '<strong style="color:orange;">⚠ HTTP ' . $status . ' ' . $response->getReasonPhrase() . '</strong>';
      }
    }
    catch (\Throwable $e) {
      return '<strong style="color:red;">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</strong>';
    }
  }
}
