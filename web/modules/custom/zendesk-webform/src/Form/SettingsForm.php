<?php
/**
 * Created by PhpStorm.
 * User: Steven
 * Date: 2019-05-18
 * Time: 8:15 AM
 */

namespace Drupal\zendesk_webform\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm
 * Contains Drupal\welcome\Form\MessagesForm.
 * @package Drupal\zendesk_webform\Form
 * @file
 */
class SettingsForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'zendesk_webform.adminsettings'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'zendesk_webform_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $config = $this->config('zendesk_webform.adminsettings');

        $form['subdomain'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Zendesk Subdomain'),
            '#description' => $this->t('Add the subdomain for your Zendesk account'),
            '#default_value' => $config->get('subdomain')
        ];

        $form['user_email'] = [
            '#type' => 'email',
            '#title' => $this->t('Zendesk Account Email Address'),
            '#description' => $this->t('The email address of your Zendesk account'),
            '#default_value' => $config->get('user_email')
        ];

        $form['web_token'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Zendesk API Token'),
            '#description' => $this->t('The API Token required to connect to Zendesk'),
            '#default_value' => $config->get('web_token')
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitForm($form, $form_state);

        $this->config('zendesk_webform.adminsettings')
            ->set('subdomain',$form_state->getValue('subdomain'))
            ->set('user_email',$form_state->getValue('user_email'))
            ->set('web_token',$form_state->getValue('web_token'))
            ->save()
        ;
    }
}