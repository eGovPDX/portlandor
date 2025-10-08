<?php

namespace Drupal\portland_webforms\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'portland_webforms.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'portland_webforms_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('portland_webforms.settings');

    $form['azure_storage_key_id'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Azure Storage Connection String key'),
      '#description' => $this->t('The key that contains the Azure Storage connection string for uploading all webform submissions to Azure Blob Storage.'),
      '#default_value' => $config->get('azure_storage_key_id') ?: '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('portland_webforms.settings')
      ->set('azure_storage_key_id', $form_state->getValue('azure_storage_key_id'))
      ->save();
  }
}
