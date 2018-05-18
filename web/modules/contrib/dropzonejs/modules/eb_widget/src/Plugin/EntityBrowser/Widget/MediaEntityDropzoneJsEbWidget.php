<?php

namespace Drupal\dropzonejs_eb_widget\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Drupal\dropzonejs\DropzoneJsUploadSaveInterface;
use Drupal\dropzonejs\Events\DropzoneMediaEntityCreateEvent;
use Drupal\dropzonejs\Events\Events;
use Drupal\entity_browser\WidgetValidationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides an Entity Browser widget that uploads media entities.
 *
 * Widget will upload files and attach them to the media entity of type that is
 * defined in the configuration.
 *
 * @EntityBrowserWidget(
 *   id = "dropzonejs_media_entity",
 *   label = @Translation("Media Entity DropzoneJS"),
 *   description = @Translation("Adds DropzoneJS upload integration that saves Media entities."),
 *   auto_select = TRUE
 * )
 */
class MediaEntityDropzoneJsEbWidget extends DropzoneJsEbWidget {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs widget plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\dropzonejs\DropzoneJsUploadSaveInterface $dropzonejs_upload_save
   *   The upload saving dropzonejs service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param Token $token
   *   The token service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, DropzoneJsUploadSaveInterface $dropzonejs_upload_save, AccountProxyInterface $current_user, Token $token, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $dropzonejs_upload_save, $current_user, $token);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('dropzonejs.upload_save'),
      $container->get('current_user'),
      $container->get('token'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media_type' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * Returns the media type that this widget creates.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   Media type.
   */
  protected function getType() {
    return $this->entityTypeManager
      ->getStorage('media_type')
      ->load($this->configuration['media_type']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['media_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Media type'),
      '#required' => TRUE,
      '#description' => $this->t('The type of media entity to create from the uploaded file(s).'),
    ];

    $media_type = $this->getType();
    if ($media_type) {
      $form['media_type']['#default_value'] = $media_type->id();
    }

    $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();

    if (!empty($media_types)) {
      foreach ($media_types as $media_type) {
        $form['media_type']['#options'][$media_type->id()] = $media_type->label();
      }
    }
    else {
      $form['media_type']['#disabled'] = TRUE;
      $form['media_type']['#description'] = $this->t('You must @create_media_type before using this widget.', [
        '@create_media_type' => Link::createFromRoute($this->t('create a media type'), 'media.add')->toString(),
      ]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // Depend on the media type this widget creates.
    $media_type = $this->getType();
    $dependencies[$media_type->getConfigDependencyKey()][] = $media_type->getConfigDependencyName();
    $dependencies['module'][] = 'media';

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareEntities(array $form, FormStateInterface $form_state) {
    $entities = [];
    $media_type = $this->getType();

    foreach (parent::prepareEntities($form, $form_state) as $file) {
      $entities[] = $this->entityTypeManager->getStorage('media')->create([
        'bundle' => $media_type->id(),
        $media_type->getSource()->getConfiguration()['source_field'] => $file,
        'uid' => $this->currentUser->id(),
        'status' => TRUE,
        'type' => $media_type->getSource()->getPluginId(),
      ]);
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\media\MediaInterface[] $media_entities */
    $media_entities = $this->prepareEntities($form, $form_state);
    $source_field = $this->getType()->getSource()->getConfiguration()['source_field'];

    foreach ($media_entities as &$media_entity) {
      $file = $media_entity->$source_field->entity;
      /** @var \Drupal\dropzonejs\Events\DropzoneMediaEntityCreateEvent $event */
      $event = $this->eventDispatcher->dispatch(Events::MEDIA_ENTITY_CREATE, new DropzoneMediaEntityCreateEvent($media_entity, $file, $form, $form_state, $element));
      $media_entity = $event->getMediaEntity();
      $source_field = $media_entity->getSource()->getConfiguration()['source_field'];
      // If we don't save file at this point Media entity creates another file
      // entity with same uri for the thumbnail. That should probably be fixed
      // in Media entity, but this workaround should work for now.
      $media_entity->$source_field->entity->save();
      $media_entity->save();
    }

    $this->selectEntities($media_entities, $form_state);
    $this->clearFormValues($element, $form_state);
  }

}
