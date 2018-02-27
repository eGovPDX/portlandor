<?php

namespace Drupal\image_widget_crop\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\image_widget_crop\ImageWidgetCropInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\crop\Entity\CropType;

/**
 * Plugin implementation of the 'image_widget_crop' widget.
 *
 * @FieldWidget(
 *   id = "image_widget_crop",
 *   label = @Translation("ImageWidget crop"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageCropWidget extends ImageWidget {

  /**
   * Instance of ImageWidgetCropManager object.
   *
   * @var \Drupal\image_widget_crop\ImageWidgetCropInterface
   */
  protected $imageWidgetCropManager;

  /**
   * The image style storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The crop type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $cropTypeStorage;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, ImageWidgetCropInterface $iwc_manager, EntityStorageInterface $image_style_storage, ConfigEntityStorageInterface $crop_type_storage, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info);
    $this->imageWidgetCropManager = $iwc_manager;
    $this->imageStyleStorage = $image_style_storage;
    $this->cropTypeStorage = $crop_type_storage;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('element_info'),
      $container->get('image_widget_crop.manager'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('entity_type.manager')->getStorage('crop_type'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return arraystringstring|null|bool
   *   The array of settings.
   */
  public static function defaultSettings() {
    return [
      'crop_preview_image_style' => 'crop_thumbnail',
      'crop_list' => NULL,
      'show_crop_area' => FALSE,
      'show_default_crop' => TRUE,
      'warn_multiple_usages' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * Form API callback: Processes a crop_image field element.
   *
   * Expands the image_image type to include the alt and title fields.
   *
   * This method is assigned as a #process callback in formElement() method.
   *
   * @return array
   *   The elements with parents fields.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    if ($element['#files']) {
      foreach ($element['#files'] as $file) {
        $element['image_crop'] = [
          '#type' => 'image_crop',
          '#file' => $file,
          '#crop_type_list' => $element['#crop_list'],
          '#crop_preview_image_style' => $element['#crop_preview_image_style'],
          '#show_default_crop' => $element['#show_default_crop'],
          '#show_crop_area' => $element['#show_crop_area'],
          '#warn_multiple_usages' => $element['#warn_multiple_usages'],
        ];
      }
    }

    return parent::process($element, $form_state, $form);
  }

  /**
   * Verify if the element have an image file.
   *
   * @param array $element
   *   A form element array containing basic properties for the widget.
   * @param array $variables
   *   An array with all existent variables for render.
   *
   * @return arraystringarray
   *   The variables with width & height image informations.
   */
  public static function getFileImageVariables(array $element, array &$variables) {
    // Determine image dimensions.
    if (isset($element['#value']['width']) && isset($element['#value']['height'])) {
      $variables['width'] = $element['#value']['width'];
      $variables['height'] = $element['#value']['height'];
    }
    else {
      /** @var \Drupal\Core\Image\Image $image */
      $image = \Drupal::service('image.factory')->get($variables['uri']);
      if ($image->isValid()) {
        $variables['width'] = $image->getWidth();
        $variables['height'] = $image->getHeight();
      }
      else {
        $variables['width'] = $variables['height'] = NULL;
      }
    }

    return $variables;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['crop_preview_image_style'] = [
      '#title' => $this->t('Crop preview image style'),
      '#type' => 'select',
      '#options' => image_style_options(FALSE),
      '#default_value' => $this->getSetting('crop_preview_image_style'),
      '#description' => $this->t('The preview image will be shown while editing the content.'),
      '#weight' => 15,
    ];

    $element['crop_list'] = [
      '#title' => $this->t('Crop Type'),
      '#type' => 'select',
      '#options' => $this->imageWidgetCropManager->getAvailableCropType(CropType::getCropTypeNames()),
      '#empty_option' => $this->t('<@no-preview>', ['@no-preview' => $this->t('no preview')]),
      '#default_value' => $this->getSetting('crop_list'),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#description' => $this->t('The type of crop to apply to your image. If your Crop Type not appear here, set an image style use your Crop Type'),
      '#weight' => 16,
    ];

    $element['show_crop_area'] = [
      '#title' => $this->t('Always expand crop area'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_crop_area'),
    ];

    $element['show_default_crop'] = [
      '#title' => $this->t('Show default crop area'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_default_crop'),
    ];

    $element['warn_multiple_usages'] = [
      '#title' => $this->t('Warn the user if the crop is used more than once.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('warn_multiple_usages'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   A short summary of the widget settings.
   */
  public function settingsSummary() {
    $preview = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);

    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('preview_image_style');
    $crop_preview = $image_styles[$this->getSetting('crop_preview_image_style')];
    $crop_list = $this->getSetting('crop_list');
    $crop_show_button = $this->getSetting('show_crop_area');
    $show_default_crop = $this->getSetting('show_default_crop');
    $warn_multiple_usages = $this->getSetting('warn_multiple_usages');

    $preview[] = $this->t('Always expand crop area: @bool', ['@bool' => ($crop_show_button) ? 'Yes' : 'No']);
    $preview[] = $this->t('Show default crop area: @bool', ['@bool' => ($show_default_crop) ? 'Yes' : 'No']);
    $preview[] = $this->t('Warn the user if the crop is used more than once: @bool', ['@bool' => ($warn_multiple_usages) ? 'Yes' : 'No']);

    if (isset($image_styles[$image_style_setting])) {
      $preview[] = $this->t('Preview image style: @style', ['@style' => $image_style_setting]);
    }
    else {
      $preview[] = $this->t('No preview image style');
    }

    if (isset($crop_preview)) {
      $preview[] = $this->t('Preview crop zone image style: @style', ['@style' => $crop_preview]);
    }

    if (!empty($crop_list)) {
      $preview[] = $this->t('Crop Type used: @list', ['@list' => implode(", ", $crop_list)]);
    }

    return $preview;
  }

  /**
   * {@inheritdoc}
   *
   * @return arraystringarray
   *   The form elements for a single widget for this field.
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Add properties needed by process() method.
    $element['#crop_list'] = $this->getSetting('crop_list');
    $element['#crop_preview_image_style'] = $this->getSetting('crop_preview_image_style');
    $element['#show_crop_area'] = $this->getSetting('show_crop_area');
    $element['#show_default_crop'] = $this->getSetting('show_default_crop');
    $element['#warn_multiple_usages'] = $this->getSetting('warn_multiple_usages');

    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

}
