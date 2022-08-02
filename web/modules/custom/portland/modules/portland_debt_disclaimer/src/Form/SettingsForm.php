<?php

namespace Drupal\portland_debt_disclaimer\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm
 * Contains Drupal\welcome\Form\MessagesForm.
 *
 * @package Drupal\portland_debt_disclaimer\Form
 */
class SettingsForm extends ConfigFormBase
{
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      "portland_debt_disclaimer.settings"
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "portland_debt_disclaimer_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config("portland_debt_disclaimer.settings");

    $form["cookie_name"] = [
      "#type" => "textfield",
      "#maxlength" => 48,
      "#maxlength_js" => TRUE,
      "#title" => $this->t("Cookie name"),
      "#description" => $this->t("This should be prefixed with <a href=\"https://pantheon.io/docs/cookies#cache-varying-cookies\">STYXKEY_</a>"),
      "#default_value" => $config->get("cookie_name")
    ];

    $form["cookie_ttl_hrs"] = [
      "#type" => "number",
      "#title" => $this->t("Cookie TTL (in hours)"),
      "#description" => $this->t("How long the acknowledgement cookie lasts"),
      "#default_value" => $config->get("cookie_ttl_hrs")
    ];

    $form["path_prefix"] = [
      "#type" => "textfield",
      "#title" => $this->t("Path prefix to match"),
      "#description" => $this->t("Any path beginning with this string will require a disclaimer"),
      "#default_value" => $config->get("path_prefix")
    ];

    $form["redirect_to"] = [
      "#type" => "textfield",
      "#title" => $this->t("Path to redirect to"),
      "#description" => $this->t("Path to redirect to for disclaimer. This should be a webform that sets the cookie"),
      "#default_value" => $config->get("redirect_to")
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config("portland_debt_disclaimer.settings")
         ->set("cookie_name", $form_state->getValue("cookie_name"))
         ->set("cookie_ttl_hrs", $form_state->getValue("cookie_ttl_hrs"))
         ->set("path_prefix", $form_state->getValue("path_prefix"))
         ->set("redirect_to", $form_state->getValue("redirect_to"))
         ->save();
  }
}
