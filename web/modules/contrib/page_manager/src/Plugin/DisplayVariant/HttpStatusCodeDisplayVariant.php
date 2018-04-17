<?php

namespace Drupal\page_manager\Plugin\DisplayVariant;

use Drupal\Core\Display\ContextAwareVariantInterface;
use Drupal\Core\Display\VariantBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a variant that returns a response with an HTTP status code.
 *
 * @DisplayVariant(
 *   id = "http_status_code",
 *   admin_label = @Translation("HTTP status code")
 * )
 */
class HttpStatusCodeDisplayVariant extends VariantBase implements ContextAwareVariantInterface, ContainerFactoryPluginInterface {

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * An array of collected contexts.
   *
   * This is only used on runtime, and is not stored.
   *
   * @var \Drupal\Component\Plugin\Context\ContextInterface[]
   */
  protected $contexts = [];

  /**
   * List of codes with redirect action.
   *
   * @var array
   */
  public static $redirectCodes = [301, 302, 303, 305, 307, 308];

  /**
   * Gets the contexts.
   *
   * @return \Drupal\Component\Plugin\Context\ContextInterface[]
   *   An array of set contexts, keyed by context name.
   */
  public function getContexts() {
    return $this->contexts;
  }

  /**
   * Sets the contexts.
   *
   * @param \Drupal\Component\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts, keyed by context name.
   */
  public function setContexts(array $contexts) {
    $this->contexts = $contexts;
  }

  /**
   * Constructs a new HttpStatusCodeDisplayVariant.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AliasManagerInterface $alias_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.alias_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get all possible status codes defined by Symfony.
    $options = Response::$statusTexts;
    // Move 403/404/500 to the top.
    $options = [
      '404' => $options['404'],
      '403' => $options['403'],
      '500' => $options['500'],
    ] + $options;

    // Add the HTTP status code, so it's easier for people to find it.
    array_walk($options, function ($title, $code) use (&$options) {
      $options[$code] = $this->t('@code (@title)', ['@code' => $code, '@title' => $title]);
    });

    $form['status_code'] = [
      '#title' => $this->t('HTTP status code'),
      '#type' => 'select',
      '#default_value' => $this->configuration['status_code'],
      '#options' => $options,
    ];

    $state_conditions = [];
    foreach (self::$redirectCodes as $code) {
      $state_conditions[] = ['value' => $code];
    }
    $form['redirect_location'] = [
      '#title' => $this->t('HTTP redirect location'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['redirect_location'],
      '#states' => [
        'visible' => [
          ':input[name="variant_settings[status_code]"]' => $state_conditions,
        ],
        'required' => [
          ':input[name="variant_settings[status_code]"]' => $state_conditions,
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['status_code'] = '404';
    $configuration['redirect_location'] = '';
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['status_code'] = $form_state->getValue('status_code');
    $this->configuration['redirect_location'] = $form_state->getValue('redirect_location');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $status_code = $this->configuration['status_code'];
    if ($status_code == 200) {
      return [];
    }
    elseif (in_array($status_code, self::$redirectCodes, TRUE)) {
      $redirect_location = $this->configuration['redirect_location'];

      $params = $this->getParameterNames($redirect_location);

      $contexts = $this->getContexts();
      foreach ($params as $param) {
        if (!isset($contexts[$param])) {
          continue;
        }

        /** @var \Drupal\Component\Plugin\Context\ContextInterface $context */
        $context = $contexts[$param];
        $value = $this->variableToString($context->getContextValue());
        if ($value === FALSE) {
          continue;
        }

        $redirect_location = str_replace('{' . $param . '}', $value, $redirect_location);

        if ($alias = $this->aliasManager->getAliasByPath($redirect_location)) {
          $redirect_location = $alias;
        }
      }

      $response = new TrustedRedirectResponse($redirect_location, $status_code);
      $response->send();
      exit;
    }
    else {
      throw new HttpException($status_code);
    }
  }

  /**
   * Gets the names of all parameters for this path.
   *
   * @param string $path
   *   The path to return the parameter names for.
   *
   * @return string[]
   *   An array of parameter names for the given path.
   */
  public function getParameterNames($path) {
    if (preg_match_all('|\{(\w+)\}|', $path, $matches)) {
      return $matches[1];
    }
    return [];
  }

  /**
   * Convert variable to string.
   *
   * @param mixed $var
   *   The variable to convert into string.
   *
   * @return bool|string
   *   String representation for this variable.
   */
  protected function variableToString($var) {
    if (is_array($var)) {
      $result = FALSE;
    }
    elseif (!is_object($var)) {
      $result = settype($var, 'string') ? $var : FALSE;
    }
    elseif (is_object($var)) {
      if (method_exists($var, '__toString')) {
        $result = (string) $var;
      }
      elseif (method_exists($var, 'id')) {
        $result = $var->id();
      }
      else {
        $result = FALSE;
      }
    }
    else {
      $result = FALSE;
    }

    return $result;
  }

}
