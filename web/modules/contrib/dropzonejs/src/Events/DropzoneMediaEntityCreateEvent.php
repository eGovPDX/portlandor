<?php

namespace Drupal\dropzonejs\Events;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Represents Media Entity creation as an event when using DropzoneJS.
 */
class DropzoneMediaEntityCreateEvent extends Event {

  /**
   * The media entity being created.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $mediaEntity;

  /**
   * The file that will be used for the media entity.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * The form that contains the Dropzone element.
   *
   * @var array
   */
  protected $form;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * The Dropzone form element.
   *
   * @var array
   */
  protected $element;

  /**
   * DropzoneMediaEntityCreateEvent constructor.
   *
   * @param \Drupal\media\MediaInterface $media_entity
   *   The media entity being created.
   * @param \Drupal\file\FileInterface $file
   *   The file that will be used for the media entity.
   * @param array $form
   *   The form that contains the Dropzone element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $element
   *   The Dropzone form element.
   */
  public function __construct(MediaInterface $media_entity, FileInterface $file, array $form, FormStateInterface $form_state, array $element) {
    $this->mediaEntity = $media_entity;
    $this->file = $file;
    $this->form = $form;
    $this->formState = $form_state;
    $this->element = $element;
  }

  /**
   * Get the media entity.
   *
   * @return \Drupal\media\MediaInterface
   *   A media entity.
   */
  public function getMediaEntity() {
    return $this->mediaEntity;
  }

  /**
   * Set the media entity.
   *
   * @param \Drupal\media\MediaInterface $media_entity
   *   The updated media entity.
   */
  public function setMediaEntity(MediaInterface $media_entity) {
    $this->mediaEntity = $media_entity;
  }

  /**
   * Get the file for the media entity.
   *
   * @return \Drupal\file\FileInterface
   *   The file that will be used for the media entity.
   */
  public function getFile() {
    return $this->file;
  }

  /**
   * Get the form that contains the Dropzone element.
   *
   * @return array
   *    The form that contains the Dropzone element.
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * Get the form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *    The current formstate.
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * Set the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The updated form state.
   */
  public function setFormState(FormStateInterface $form_state) {
    $this->formState = $form_state;
  }

  /**
   * Get the Dropzone form element.
   *
   * @return array
   *    The dropzone element.
   */
  public function getElement() {
    return $this->element;
  }

  /**
   * Set the Dropzone form element.
   *
   * @param array $element
   *   The updated form element.
   */
  public function setElement(array $element) {
    $this->element = $element;
  }

}
