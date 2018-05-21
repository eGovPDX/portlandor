<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;

/**
 * JSON API integration test for the "EntityFormDisplay" config entity type.
 *
 * @group jsonapi
 */
class EntityFormDisplayTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_form_display';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'entity_form_display--entity_form_display';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer node form display']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" node type.
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ]);
    $camelids->save();

    // Create a form display.
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'camelids',
      'mode' => 'default',
    ]);
    $form_display->save();

    return $form_display;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/entity_form_display/entity_form_display/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => 'http://jsonapi.org/format/1.0/',
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => $self_url,
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'entity_form_display--entity_form_display',
        'links' => [
          'self' => $self_url,
        ],
        'attributes' => [
          'bundle' => 'camelids',
          'content' => [
            'created' => [
              'type' => 'datetime_timestamp',
              'weight' => 10,
              'region' => 'content',
              'settings' => [],
              'third_party_settings' => [],
            ],
            'promote' => [
              'type' => 'boolean_checkbox',
              'settings' => [
                'display_label' => TRUE,
              ],
              'weight' => 15,
              'region' => 'content',
              'third_party_settings' => [],
            ],
            'status' => [
              'type' => 'boolean_checkbox',
              'weight' => 120,
              'region' => 'content',
              'settings' => [
                'display_label' => TRUE,
              ],
              'third_party_settings' => [],
            ],
            'sticky' => [
              'type' => 'boolean_checkbox',
              'settings' => [
                'display_label' => TRUE,
              ],
              'weight' => 16,
              'region' => 'content',
              'third_party_settings' => [],
            ],
            'title' => [
              'type' => 'string_textfield',
              'weight' => -5,
              'region' => 'content',
              'settings' => [
                'size' => 60,
                'placeholder' => '',
              ],
              'third_party_settings' => [],
            ],
            'uid' => [
              'type' => 'entity_reference_autocomplete',
              'weight' => 5,
              'settings' => [
                'match_operator' => 'CONTAINS',
                'size' => 60,
                'placeholder' => '',
              ],
              'region' => 'content',
              'third_party_settings' => [],
            ],
          ],
          'dependencies' => [
            'config' => [
              'node.type.camelids',
            ],
          ],
          'hidden' => [],
          'id' => 'node.camelids.default',
          'langcode' => 'en',
          'mode' => 'default',
          'status' => NULL,
          'targetEntityType' => 'node',
          'uuid' => $this->entity->uuid(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'administer node form display' permission is required.";
  }

  /**
   * {@inheritdoc}
   */
  public function testGetIndividual() {
    // @todo Remove when JSON API requires Drupal 8.5 or newer.
    // @see https://www.drupal.org/project/drupal/issues/2866666
    if (floatval(\Drupal::VERSION) < 8.5) {
      $this->markTestSkipped('EntityFormDisplay entities had a dysfunctional access control handler until 8.5, this is necessary for this test coverage to work.');
    }
    return parent::testGetIndividual();
  }

}
