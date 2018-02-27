<?php
namespace Drupal\context\Plugin\ContextReaction;

use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a content reaction that will let you change theme.
 *
 * @ContextReaction(
 *   id = "theme",
 *   label = @Translation("Theme")
 * )
 */
class Theme extends ContextReactionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    ThemeManagerInterface $themeManager,
    ThemeHandlerInterface $themeHandler
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->themeManager = $themeManager;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('theme.manager'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Gives you ability to change theme.');
  }

  /**
   * Executes the plugin.
   */
  public function execute() {
    // TODO: Implement execute() method.
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $themes = $this->themeHandler->listInfo();
    $default_theme = $this->themeHandler->getDefault();

    $theme_options = [];
    foreach ($themes as $theme_id => $theme) {
      $theme_options[$theme_id] = $theme->info['name'];
    }
    $configuration = $this->getConfiguration();

    $form['theme'] = [
      '#type' => 'radios',
      '#options' => $theme_options,
      '#title' => $this->t('Select theme'),
      '#default_value' => isset($configuration['theme']) ? $configuration['theme'] : $default_theme,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration['theme'] = $form_state->getValue('theme');
    $configuration += $this->getConfiguration();
    $this->setConfiguration($configuration);
  }
}
