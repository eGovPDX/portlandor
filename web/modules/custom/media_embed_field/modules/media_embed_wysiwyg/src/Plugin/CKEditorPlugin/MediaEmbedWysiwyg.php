<?php

namespace Drupal\media_embed_wysiwyg\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\media_embed_field\Plugin\Field\FieldFormatter\Media;

/**
 * The media_entity plugin for media_embed_field.
 *
 * @CKEditorPlugin(
 *   id = "media_embed",
 *   label = @Translation("Media Embed WYSIWYG")
 * )
 */
class MediaEmbedWysiwyg extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'media_embed_wysiwyg') . '/plugin/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'media_embed' => [
        'label' => $this->t('Media Embed'),
        'image' => drupal_get_path('module', 'media_embed_wysiwyg') . '/plugin/icon.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $editor_settings = $editor->getSettings();
    $plugin_settings = NestedArray::getValue($editor_settings, [
      'plugins',
      'media_embed',
      'defaults',
      'children',
    ]);
    $settings = $plugin_settings ?: [];

    $form['defaults'] = [
      '#title' => $this->t('Default Settings'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
      'children' => Media::mockInstance($settings)->settingsForm([], new FormState()),
    ];
    return $form;
  }

}
