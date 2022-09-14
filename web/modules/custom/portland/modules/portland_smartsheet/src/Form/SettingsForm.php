<?php

namespace Drupal\portland_smartsheet\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm
 * Contains Drupal\welcome\Form\MessagesForm.
 * @package Drupal\portland_smartsheet\Form
 * @file
 */
class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'portland_smartsheet.adminsettings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'portland_smartsheet_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('portland_smartsheet.adminsettings');

    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smartsheet API Token'),
      '#description' => $this->t('The API Token required to connect to Smartsheet'),
      '#default_value' => $config->get('access_token')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('portland_smartsheet.adminsettings')
      ->set('access_token',$form_state->getValue('access_token'))
      ->save();
  }
}
