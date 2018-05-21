<?php

namespace Drupal\ds_extras\Plugin\DsField;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ds\Plugin\DsField\DsFieldBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin that generates a link to switch view mode with via ajax.
 *
 * @DsField(
 *   id = "switch_field",
 *   title = @Translation("Switch field"),
 *   entity_type = "node"
 * )
 */
class SwitchField extends DsFieldBase {

  /**
   * The EntityDisplayRepository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Display Suite field plugin.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager = $entity_type_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $settings = $this->getConfiguration();

    if (!empty($settings)) {
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->entity();

      // Basic route parameters.
      $route_parameters = [
        'entityType' => $entity->getEntityTypeId(),
        'entityId' => $entity->id(),
      ];

      $selector = $this->viewMode() == 'default' ? 'full' : $this->viewMode();
      // Basic route options.
      $route_options = [
        'query' => [
          'selector' => 'view-mode-' . $selector,
        ],
        'attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
      ];

      foreach ($settings['vms'] as $key => $value) {
        // If the label is empty, do not create a link.
        if (!empty($value)) {
          $route_parameters['viewMode'] = $key == 'default' ? 'full' : $key;
          $items[] = \Drupal::l($value, Url::fromRoute('ds_extras.switch_view_mode', $route_parameters, $route_options));
        }
      }
    }

    $output = [];
    if (!empty($items)) {
      $output = [
        '#theme' => 'item_list',
        '#items' => $items,
        // Add the AJAX library to the field for inline switching support.
        '#attached' => [
          'library' => [
            'core/drupal.ajax',
          ],
        ],
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $entity_type = $this->getEntityTypeId();
    $bundle = $this->bundle();
    $view_modes = $this->entityDisplayRepository->getViewModes($entity_type);

    $form['info'] = [
      '#markup' => $this->t('Enter a label for the link for the view modes you want to switch to.<br />Leave empty to hide link. They will be localized.'),
    ];

    $config = $this->getConfiguration();
    $config = isset($config['vms']) ? $config['vms'] : [];
    foreach ($view_modes as $key => $value) {
      $entity_display = $this->entityTypeManager
        ->getStorage('entity_view_display')
        ->load($entity_type . '.' . $bundle . '.' . $key);
      if (!empty($entity_display)) {
        if ($entity_display->status()) {
          $form['vms'][$key] = [
            '#type' => 'textfield',
            '#default_value' => isset($config[$key]) ? $config[$key] : '',
            '#size' => 20,
            '#title' => Html::escape($value['label']),
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $entity_type = $this->getEntityTypeId();
    $bundle = $this->bundle();
    $settings = isset($settings['vms']) ? $settings['vms'] : [];
    $view_modes = $this->entityDisplayRepository->getViewModes($entity_type);

    $summary[] = 'View mode labels';

    foreach ($view_modes as $key => $value) {
      $entity_display = $this->entityTypeManager
        ->getStorage('entity_view_display')
        ->load($entity_type . '.' . $bundle . '.' . $key);
      if (!empty($entity_display)) {
        if ($entity_display->status()) {
          $label = isset($settings[$key]) ? $settings[$key] : $key;
          $summary[] = $key . ' : ' . $label;
        }
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    if (\Drupal::config('ds_extras.settings')->get('switch_field')) {
      return TRUE;
    }

    return FALSE;
  }

}
