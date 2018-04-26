<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin that renders a link.
 */
abstract class Link extends Field {

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['link text'] = [
      '#type' => 'textfield',
      '#title' => 'Link text',
      '#default_value' => $config['link text'],
    ];
    $form['link class'] = [
      '#type' => 'textfield',
      '#title' => 'Link class',
      '#default_value' => $config['link class'],
      '#description' => $this->t('Put a class on the link. Eg: btn btn-default'),
    ];
    $form['wrapper'] = [
      '#type' => 'textfield',
      '#title' => 'Wrapper',
      '#default_value' => $config['wrapper'],
      '#description' => $this->t('Eg: h1, h2, p'),
    ];
    $form['class'] = [
      '#type' => 'textfield',
      '#title' => 'Class',
      '#default_value' => $config['class'],
      '#description' => $this->t('Put a class on the wrapper. Eg: block-title'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();

    $summary = [];
    $summary[] = 'Link text: ' . $config['link text'];
    if (!empty($config['link class'])) {
      $summary[] = 'Link class: ' . $config['link class'];
    }
    if (!empty($config['wrapper'])) {
      $summary[] = 'Wrapper: ' . $config['wrapper'];
    }
    if (!empty($config['class'])) {
      $summary[] = 'Class: ' . $config['class'];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $configuration = [
      'link text' => 'Read more',
      'link class' => '',
      'wrapper' => '',
      'class' => '',
      'link' => 1,
    ];

    return $configuration;
  }

}
