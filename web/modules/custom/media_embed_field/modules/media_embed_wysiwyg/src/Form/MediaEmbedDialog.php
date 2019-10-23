<?php

namespace Drupal\media_embed_wysiwyg\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\image\Entity\ImageStyle;
use Drupal\media_embed_field\Plugin\Field\FieldFormatter\Media;
use Drupal\media_embed_field\ProviderManager;
use Drupal\media_embed_field\ProviderPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class for a media embed dialog.
 */
class MediaEmbedDialog extends FormBase {

  /**
   * The media provider manager.
   *
   * @var \Drupal\media_embed_field\ProviderManager
   */
  protected $providerManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * MediaEmbedDialog constructor.
   *
   * @param \Drupal\media_embed_field\ProviderManager $provider_manager
   *   The media provider plugin manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ProviderManager $provider_manager, RendererInterface $renderer) {
    $this->providerManager = $provider_manager;
    $this->render = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('media_embed_field.provider_manager'), $container->get('renderer'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // Add AJAX support.
    $form['#prefix'] = '<div id="media-embed-dialog-form">';
    $form['#suffix'] = '</div>';
    // Ensure relevant dialog libraries are attached.
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    // Simple URL field and submit button for media URL.
    $form['media_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media URL'),
      '#required' => TRUE,
      '#default_value' => $this->getUserInput($form_state, 'media_url'),
    ];

    // If no settings are found, use the defaults configured in the filter
    // formats interface.
    $settings = $this->getUserInput($form_state, 'settings');
    if (empty($settings) && $editor = Editor::load($filter_format->id())) {
      $editor_settings = $editor->getSettings();
      $plugin_settings = NestedArray::getValue($editor_settings, [
        'plugins',
        'media_embed',
        'defaults',
        'children',
      ]);
      $settings = $plugin_settings ? $plugin_settings : [];
    }

    // Create a settings form from the existing media formatter.
    $form['settings'] = Media::mockInstance($settings)->settingsForm([], new FormState());;
    $form['settings']['#type'] = 'fieldset';
    $form['settings']['#title'] = $this->t('Settings');

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
        'wrapper' => 'media-embed-dialog-form',
      ],
    ];
    return $form;
  }

  /**
   * Get a value from the widget in the WYSIWYG.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to extract values from.
   * @param string $key
   *   The key to get from the selected WYSIWYG element.
   *
   * @return string
   *   The default value.
   */
  protected function getUserInput(FormStateInterface $form_state, $key) {
    return isset($form_state->getUserInput()['editor_object'][$key]) ? $form_state->getUserInput()['editor_object'][$key] : '';
  }

  /**
   * Get the values from the form and provider required for the client.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state from the dialog submission.
   * @param \Drupal\media_embed_field\ProviderPluginInterface $provider
   *   The provider loaded from the user input.
   *
   * @return array
   *   An array of values sent to the client for use in the WYSIWYG.
   */
  protected function getClientValues(FormStateInterface $form_state, ProviderPluginInterface $provider) {
    // All settings from the field formatter exist in the form and are relevant
    // for the rendering of the media.
    $media_formatter_settings = Media::defaultSettings();
    foreach ($media_formatter_settings as $key => $default) {
      $media_formatter_settings[$key] = $form_state->getValue($key);
    }

    $provider->downloadThumbnail();
    $thumbnail_preview = ImageStyle::load('media_embed_wysiwyg_preview')->buildUrl($provider->getLocalThumbnailUri());
    $thumbnail_preview_parts = parse_url($thumbnail_preview);
    return [
      'preview_thumbnail' => $thumbnail_preview_parts['path'] . (!empty($thumbnail_preview_parts['query']) ? '?' : '') . $thumbnail_preview_parts['query'],
      'media_url' => $form_state->getValue('media_url'),
      'settings' => $media_formatter_settings,
      'settings_summary' => Media::mockInstance($media_formatter_settings)->settingsSummary(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $provider = $this->getProvider($form_state->getValue('media_url'));
    // Display an error if no provider can be loaded for this media.
    if (FALSE == $provider) {
      $form_state->setError($form['media_url'], $this->t('Could not find a media provider to handle the given URL.'));
      return;
    }
  }

  /**
   * An AJAX submit callback to validate the WYSIWYG modal.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!$form_state->getErrors()) {
      // Load the provider and get the information needed for the client.
      $provider = $this->getProvider($form_state->getValue('media_url'));
      $response->addCommand(new EditorDialogSave($this->getClientValues($form_state, $provider)));
      $response->addCommand(new CloseModalDialogCommand());
    }
    else {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand(NULL, $form));
    }
    return $response;
  }

  /**
   * Get a provider from some input.
   *
   * @param string $input
   *   The input string.
   *
   * @return bool|\Drupal\media_embed_field\ProviderPluginInterface
   *   A media provider or FALSE on failure.
   */
  protected function getProvider($input) {
    return $this->providerManager->loadProviderFromInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The AJAX commands were already added in the AJAX callback. Do nothing in
    // the submit form.
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_embed_dialog';
  }

}
