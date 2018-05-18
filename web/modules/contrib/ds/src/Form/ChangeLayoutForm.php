<?php

namespace Drupal\ds\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Ds;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for configurable actions.
 */
class ChangeLayoutForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * ChangeLayoutForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ds_change_layout';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = '', $bundle = '', $display_mode = '', $new_layout = '') {

    $old_layout = NULL;
    $all_layouts = Ds::getLayouts();

    if (!empty($entity_type) && !empty($bundle) && !empty($display_mode)) {
      $display = entity_get_display($entity_type, $bundle, $display_mode);
      $old_layout = $display->getThirdPartySettings('ds');
    }

    if ($old_layout && isset($all_layouts[$new_layout])) {

      $new_layout_key = $new_layout;
      $new_layout = $all_layouts[$new_layout];
      $old_layout_info = $all_layouts[$old_layout['layout']['id']];

      $form['#entity_type'] = $entity_type;
      $form['#entity_bundle'] = $bundle;
      $form['#mode'] = $display_mode;
      $form['#old_layout'] = $old_layout;
      $form['#old_layout_info'] = $old_layout_info;
      $form['#new_layout'] = $new_layout;
      $form['#new_layout_key'] = $new_layout_key;

      $form['info'] = [
        '#markup' => $this->t('You are changing from @old to @new layout for @bundle in @view_mode view mode.',
          [
            '@old' => $old_layout_info->getLabel(),
            '@new' => $new_layout->getLabel(),
            '@bundle' => $bundle,
            '@view_mode' => $display_mode,
          ]
        ),
        '#prefix' => "<div class='change-ds-layout-info'>",
        '#suffix' => "</div>",
      ];

      // Old region options.
      $regions = [];
      foreach ($old_layout_info->getRegions() as $key => $info) {
        $regions[$key] = $info['label'];
      }

      // Let other modules alter the regions.
      // For old regions.
      $context = [
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'view_mode' => $display_mode,
      ];
      $region_info = [
        'region_options' => $regions,
      ];
      \Drupal::moduleHandler()->alter('ds_layout_region', $context, $region_info);
      $regions = $region_info['region_options'];

      $save_regions = [];
      foreach ($regions as $key => $info) {
        $save_regions[$key] = [
          'label' => $info,
        ];
      }
      $form['#old_layout_info']->setRegions($save_regions);

      // For new regions.
      $new_regions = [];
      foreach ($new_layout->getRegions() as $key => $info) {
        $new_regions[$key] = $info['label'];
      }
      $region_info = [
        'region_options' => $new_regions,
      ];
      \Drupal::moduleHandler()->alter('ds_layout_region', $context, $region_info);
      $new_layout->setRegions($region_info['region_options']);

      // Display the region options.
      $selectable_regions = ['' => $this->t('- None -')] + $new_layout->getRegions();
      $form['regions_pre']['#markup'] = '<div class="ds-layout-regions">';
      foreach ($regions as $region => $region_title) {
        $form['region_' . $region] = [
          '#type' => 'container',
        ];
        $form['region_' . $region]['ds_label_' . $region] = [
          '#markup' => 'Fields in <span class="change-ds-layout-old-region"> ' . $region_title . '</span> go into',
        ];
        $form['region_' . $region]['ds_' . $region] = [
          '#type' => 'select',
          '#options' => $layout_options = $selectable_regions,
          '#default_value' => $region,
        ];
      }
      $form['regions_post']['#markup'] = '</div>';

      // Show previews from old and new layouts.
      $form['preview'] = [
        '#type' => 'container',
        '#prefix' => '<div class="ds-layout-preview">',
        '#suffix' => '</div>',
      ];

      $fallback_image = drupal_get_path('module', 'ds') . '/images/preview.png';
      $old_image = $old_layout_info->getIconPath() ?: $fallback_image;
      $new_image = $new_layout->getIconPath() ?: $fallback_image;
      $arrow = drupal_get_path('module', 'ds') . '/images/arrow.png';

      $form['preview']['old_layout'] = [
        '#markup' => '<div class="ds-layout-preview-image"><img src="' . base_path() . $old_image . '"/></div>',
      ];
      $form['preview']['arrow'] = [
        '#markup' => '<div class="ds-layout-preview-arrow"><img src="' . base_path() . $arrow . '"/></div>',
      ];
      $form['preview']['new_layout'] = [
        '#markup' => '<div class="ds-layout-preview-image"><img src="' . base_path() . $new_image . '"/></div>',
      ];
      $form['#attached']['library'][] = 'ds/admin';

      // Submit button.
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#prefix' => '<div class="ds-layout-change-save">',
        '#suffix' => '</div>',
      ];
    }
    else {
      $form['nothing'] = ['#markup' => $this->t('No valid configuration found.')];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Prepare some variables.
    $old_layout = $form['#old_layout'];
    $new_layout = $form['#new_layout'];
    $old_layout_info = $form['#old_layout_info'];
    $new_layout_key = $form['#new_layout_key'];
    $entity_type = $form['#entity_type'];
    $bundle = $form['#entity_bundle'];
    $display_mode = $form['#mode'];

    // Create new third party settings.
    $third_party_settings = $old_layout;
    $third_party_settings['layout']['id'] = $new_layout_key;
    if ($library = $new_layout->getLibrary()) {
      $third_party_settings['layout']['library'] = $library;
    }
    unset($third_party_settings['regions']);

    // Map old regions to new ones.
    foreach ($old_layout_info->getRegions() as $region => $region_title) {
      $new_region = $form_state->getValue('ds_' . $region);
      if ($new_region != '' && isset($old_layout['regions'][$region])) {
        foreach ($old_layout['regions'][$region] as $field) {
          if (!isset($third_party_settings['regions'][$new_region])) {
            $third_party_settings['regions'][$new_region] = [];
          }
          $third_party_settings['regions'][$new_region][] = $field;
        }
      }
    }

    // Save configuration.
    /* @var $entity_display \Drupal\Core\Entity\Display\EntityDisplayInterface*/
    $entity_display = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load($entity_type . '.' . $bundle . '.' . $display_mode);
    foreach (array_keys($third_party_settings) as $key) {
      $entity_display->setThirdPartySetting('ds', $key, $third_party_settings[$key]);
    }
    $entity_display->save();

    // Clear entity info cache.
    $this->entityFieldManager->clearCachedFieldDefinitions();

    // Show message.
    drupal_set_message(t('The layout change has been saved.'));
  }

}
