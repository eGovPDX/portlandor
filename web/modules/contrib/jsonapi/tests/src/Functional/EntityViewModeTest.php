<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Url;

/**
 * JSON API integration test for the "EntityViewMode" config entity type.
 *
 * @group jsonapi
 */
class EntityViewModeTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo: Remove 'field_ui' when https://www.drupal.org/node/2867266.
   */
  public static $modules = ['user', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_view_mode';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'entity_view_mode--entity_view_mode';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\Core\Entity\EntityViewModeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer display modes']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $entity_view_mode = EntityViewMode::create([
      'id' => 'user.test',
      'label' => 'Test',
      'targetEntityType' => 'user',
    ]);
    $entity_view_mode->save();
    return $entity_view_mode;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/entity_view_mode/entity_view_mode/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'entity_view_mode--entity_view_mode',
        'links' => [
          'self' => $self_url,
        ],
        'attributes' => [
          'cache' => TRUE,
          'dependencies' => [
            'module' => [
              'user',
            ],
          ],
          'id' => 'user.test',
          'label' => 'Test',
          'langcode' => 'en',
          'status' => TRUE,
          'targetEntityType' => 'user',
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

}
