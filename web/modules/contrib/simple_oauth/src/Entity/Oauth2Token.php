<?php

namespace Drupal\simple_oauth\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Oauth2 Token entity.
 *
 * @ingroup simple_oauth
 *
 * @ContentEntityType(
 *   id = "oauth2_token",
 *   label = @Translation("OAuth2 token"),
 *   bundle_label = @Translation("Token type"),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_oauth\Oauth2TokenListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\simple_oauth\Entity\Form\Oauth2TokenDeleteForm",
 *     },
 *     "access" = "Drupal\simple_oauth\AccessTokenAccessControlHandler",
 *   },
 *   base_table = "oauth2_token",
 *   admin_permission = "administer simple_oauth entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "value",
 *     "bundle" = "bundle",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "oauth2_token_type",
 *   links = {
 *     "canonical" = "/admin/content/simple_oauth/{oauth2_token}",
 *     "delete-form" = "/admin/content/simple_oauth/{oauth2_token}/delete"
 *   }
 * )
 */
class Oauth2Token extends ContentEntityBase implements Oauth2TokenInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Access Token entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Access Token entity.'))
      ->setReadOnly(TRUE);

    $fields['bundle'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The bundle property.'))
      ->setRevisionable(FALSE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setSetting('target_type', 'oauth2_token_type');

    $fields['auth_user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user ID of the user this access token is authenticating.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
        'weight' => 1,
      ])
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);

    $fields['client'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Client'))
      ->setDescription(t('The consumer client for this Access Token.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'consumer')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ]);

    $fields['scopes'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Scopes'))
      ->setDescription(t('The scopes for this Access Token. OAuth2 scopes are implemented as Drupal roles.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user_role')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 3,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ]);

    $fields['value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setDescription(t('The token value.'))
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 4,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 5,
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 6,
      ]);

    $fields['expire'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expire'))
      ->setDescription(t('The time when the token expires.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 7,
      ])
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the token is available.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 8,
      ])
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function revoke() {
    $this->set('status', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isRevoked() {
    return !$this->get('status')->value;
  }

}
