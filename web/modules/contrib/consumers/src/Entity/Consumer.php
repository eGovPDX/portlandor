<?php

namespace Drupal\consumers\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Consumer entity.
 *
 * @ContentEntityType(
 *   id = "consumer",
 *   label = @Translation("Consumer"),
 *   handlers = {
 *     "list_builder" = "Drupal\consumers\ConsumerListBuilder",
 *     "form" = {
 *       "default" = "Drupal\consumers\Entity\Form\ConsumerForm",
 *       "add" = "Drupal\consumers\Entity\Form\ConsumerForm",
 *       "edit" = "Drupal\consumers\Entity\Form\ConsumerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\consumers\AccessControlHandler",
 *   },
 *   base_table = "consumer",
 *   admin_permission = "administer consumer entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/consumer/{consumer}",
 *     "collection" = "/admin/config/services/consumer",
 *     "add-form" = "/admin/config/services/consumer/{consumer}/add",
 *     "edit-form" = "/admin/config/services/consumer/{consumer}/edit",
 *     "delete-form" = "/admin/config/services/consumer/{consumer}/delete"
 *   }
 * )
 */
class Consumer extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->get('owner_id')->entity) {
        $translation->set('owner_id', 0);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['owner_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Authored by'))
      ->setDescription(new TranslatableMarkup('The username of the consumer author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\consumers\Entity\Consumer::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Label'))
      ->setDescription(new TranslatableMarkup('The consumer label.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the consumer. This text will be shown to the users to authorize sharing their data to create an access token.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Logo'))
      ->setDescription(t('Logo of the consumer.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'image',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'image',
        'weight' => -3,
        'settings' => [
          'preview_image_style' => 'thumbnail',
          'progress_indicator' => 'throbber',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['third_party'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Is this consumer 3rd party?'))
      ->setDescription(new TranslatableMarkup('Mark this if the organization behind this consumer is not the same as the one behind the Drupal API.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
         'weight' => 4,
      ])
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(FALSE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
