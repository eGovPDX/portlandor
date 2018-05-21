<?php

namespace Drupal\simple_oauth\Entity\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simple_oauth\Service\Filesystem\FilesystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
class Oauth2TokenSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\simple_oauth\Service\Filesystem\FilesystemInterface
   */
  protected $fileSystem;

  /**
   * Oauth2TokenSettingsForm constructor.
   *
   * @param \Drupal\simple_oauth\Service\Filesystem\FilesystemInterface $filesystem
   */
  public function __construct(ConfigFactoryInterface $configFactory, FilesystemInterface $file_system) {
    parent::__construct($configFactory);
    $this->fileSystem = $file_system;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('simple_oauth.filesystem')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'oauth2_token_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_oauth.settings'];
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
    $settings = $this->config('simple_oauth.settings');
    $settings->set('access_token_expiration', $form_state->getValue('access_token_expiration'));
    $settings->set('refresh_token_expiration', $form_state->getValue('refresh_token_expiration'));
    $settings->set('public_key', $form_state->getValue('public_key'));
    $settings->set('private_key', $form_state->getValue('private_key'));
    $settings->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Defines the settings form for Access Token entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['access_token_expiration'] = [
      '#type' => 'number',
      '#title' => $this->t('Access token expiration time'),
      '#description' => $this->t('The default value, in seconds, to be used as expiration time when creating new tokens.'),
      '#default_value' => $this->config('simple_oauth.settings')
        ->get('access_token_expiration'),
    ];
    $form['refresh_token_expiration'] = [
      '#type' => 'number',
      '#title' => $this->t('Refresh token expiration time'),
      '#description' => $this->t('The default value, in seconds, to be used as expiration time when creating new tokens.'),
      '#default_value' => $this->config('simple_oauth.settings')
        ->get('refresh_token_expiration'),
    ];
    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key'),
      '#description' => $this->t('The path to the public key file.'),
      '#default_value' => $this->config('simple_oauth.settings')
        ->get('public_key'),
      '#element_validate' => ['::validateExistingFile'],
      '#required' => TRUE,
      '#attributes' => ['id' => 'pubk'],
    ];
    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#description' => $this->t('The path to the private key file.'),
      '#default_value' => $this->config('simple_oauth.settings')
        ->get('private_key'),
      '#element_validate' => ['::validateExistingFile'],
      '#required' => TRUE,
      '#attributes' => ['id' => 'pk'],
    ];

    $form['actions'] = [
      'actions' => [
        '#cache' => ['max-age' => 0],
        '#weight' => 20,
      ],
    ];

    // Generate Key Modal Button if openssl extension is enabled.
    if ($this->fileSystem->isExtensionEnabled('openssl')) {
      // Generate Modal Button.
      $form['actions']['generate']['keys'] = [
        '#type' => 'link',
        '#title' => $this->t('Generate keys'),
        '#url' => Url::fromRoute(
          'oauth2_token.settings.generate_key',
          [],
          ['query' => ['pubk_id' => 'pubk', 'pk_id' => 'pk']]
        ),
        '#attributes' => [
          'class' => ['use-ajax', 'button'],
        ],
      ];

      // Attach Drupal Modal Dialog library.
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }
    else {
      // Generate Notice Info Message about enabling openssl extension.
      drupal_set_message(
        $this->t('Enabling the PHP OpenSSL Extension will permit you generate the keys from this form.'),
        'warning'
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validates if the file exists.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateExistingFile(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#value'])) {
      $path = $element['#value'];
      // Does the file exist?
      if (!$this->fileSystem->fileExist($path)) {
        $form_state->setError($element, $this->t('The %field file does not exist.', ['%field' => $element['#title']]));
      }
      // Is the file readable?
      if (!$this->fileSystem->isReadable($path)) {
        $form_state->setError($element, $this->t('The %field file at the specified location is not readable.', ['%field' => $element['#title']]));
      }
    }
  }

}
