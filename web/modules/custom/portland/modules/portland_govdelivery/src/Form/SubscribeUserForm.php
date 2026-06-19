<?php
namespace Drupal\portland_govdelivery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\portland_govdelivery\Service\TopicsProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for subscribing a user to GovDelivery topics.
 */
class SubscribeUserForm extends FormBase {
  protected TopicsProvider $topicsProvider;

  public function __construct(TopicsProvider $topics_provider) {
    $this->topicsProvider = $topics_provider;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('portland_govdelivery.topics_provider'),
    );
  }

  public function getFormId() {
    return 'portland_govdelivery_subscribe_user_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div class="govdelivery-subscribe-form">';
    $form['#suffix'] = '</div>';

    // GovDelivery status panel (duplicated from settings form).
    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('GovDelivery status'),
      '#open' => TRUE,
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
      $test_result = $this->testConnection($endpoint);
      $form['status']['test'] = [
        '#type' => 'item',
        '#title' => $this->t('Connection test'),
        '#markup' => $test_result,
      ];
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

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
      '#description' => $this->t('Email address to subscribe.'),
    ];

    // Get topic options.
    try {
      $topic_options = $this->topicsProvider->getWebformOptions();
    }
    catch (\Throwable $e) {
      $topic_options = [];
      $this->messenger()->addWarning($this->t('Unable to load topics: @msg', ['@msg' => $e->getMessage()]));
    }

    // Topics multiselect with Select2 enhancement if available.
    $form['topics'] = [
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

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe user'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = trim((string) $form_state->getValue('email'));
    $topics = (array) $form_state->getValue('topics');

    if (empty($email) || empty($topics)) {
      $this->messenger()->addError($this->t('Please provide an email address and select at least one topic.'));
      return;
    }

    // Determine locale from the current user.
    $langcode = \Drupal::currentUser()->getPreferredLangcode() ?: \Drupal::languageManager()->getCurrentLanguage()->getId();

    try {
      /** @var \Drupal\portland_govdelivery\Service\GovDeliveryClient $client */
      $client = \Drupal::service('portland_govdelivery.client');
      $result = $client->subscribeUser($email, $topics, $langcode);
      
      // Subscriptions endpoint handles both new and existing subscribers transparently.
      $this->messenger()->addStatus($this->t('Successfully subscribed %email to selected topics.', ['%email' => $email]));
      
      // Clear form values after successful submission.
      $form_state->setRebuild(TRUE);
      $form_state->setUserInput([]);
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
  protected function testConnection(string $endpoint): string {
    try {
      $http_client = \Drupal::httpClient();
      $config = \Drupal::config('portland_govdelivery.settings');
      $kr = \Drupal::service('key.repository');
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
