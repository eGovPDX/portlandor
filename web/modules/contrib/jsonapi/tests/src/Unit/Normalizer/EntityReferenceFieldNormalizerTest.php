<?php

namespace Drupal\Tests\jsonapi\Unit\Normalizer;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi\Normalizer\EntityReferenceFieldNormalizer;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\EntityReferenceFieldNormalizer
 * @group jsonapi
 *
 * @internal
 */
class EntityReferenceFieldNormalizerTest extends UnitTestCase {

  /**
   * The normalizer under test.
   *
   * @var \Drupal\jsonapi\Normalizer\EntityReferenceFieldNormalizer
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $link_manager = $this->prophesize(LinkManager::class);
    $field_manager = $this->prophesize(EntityFieldManagerInterface::class);
    $field_definition = $this->prophesize(FieldConfig::class);
    $item_definition = $this->prophesize(FieldItemDataDefinition::class);
    $item_definition->getMainPropertyName()->willReturn('bunny');
    $item_definition->getSetting('target_type')->willReturn('fake_entity_type');
    $item_definition->getSetting('handler_settings')->willReturn([
      'target_bundles' => ['dummy_bundle'],
    ]);
    $field_definition->getItemDefinition()
      ->willReturn($item_definition->reveal());
    $storage_definition = $this->prophesize(FieldStorageDefinitionInterface::class);
    $storage_definition->isMultiple()->willReturn(TRUE);
    $field_definition->getFieldStorageDefinition()->willReturn($storage_definition->reveal());

    $field_definition2 = $this->prophesize(FieldConfig::class);
    $field_definition2->getItemDefinition()
      ->willReturn($item_definition->reveal());
    $storage_definition2 = $this->prophesize(FieldStorageDefinitionInterface::class);
    $storage_definition2->isMultiple()->willReturn(FALSE);
    $field_definition2->getFieldStorageDefinition()->willReturn($storage_definition2->reveal());

    $field_manager->getFieldDefinitions('fake_entity_type', 'dummy_bundle')
      ->willReturn([
        'field_dummy' => $field_definition->reveal(),
        'field_dummy_single' => $field_definition2->reveal(),
      ]);
    $plugin_manager = $this->prophesize(FieldTypePluginManagerInterface::class);
    $plugin_manager->createFieldItemList(
      Argument::type(FieldableEntityInterface::class),
      Argument::type('string'),
      Argument::type('array')
    )->willReturnArgument(2);
    $resource_type_repository = $this->prophesize(ResourceTypeRepository::class);
    $resource_type_repository->get('fake_entity_type', 'dummy_bundle')
      ->willReturn(new ResourceType('lorem', 'dummy_bundle', NULL));

    $entity = $this->prophesize(EntityInterface::class);
    $entity->uuid()->willReturn('4e6cb61d-4f04-437f-99fe-42c002393658');
    $entity->id()->willReturn(42);
    $entity_repository = $this->prophesize(EntityRepositoryInterface::class);
    $entity_repository->loadEntityByUuid('lorem', '4e6cb61d-4f04-437f-99fe-42c002393658')
      ->willReturn($entity->reveal());

    $this->normalizer = new EntityReferenceFieldNormalizer(
      $link_manager->reveal(),
      $field_manager->reveal(),
      $plugin_manager->reveal(),
      $resource_type_repository->reveal(),
      $entity_repository->reveal()
    );
  }

  /**
   * @covers ::denormalize
   * @dataProvider denormalizeProvider
   */
  public function testDenormalize($input, $field_name, $expected) {
    $entity = $this->prophesize(FieldableEntityInterface::class);
    $context = [
      'resource_type' => new ResourceType('fake_entity_type', 'dummy_bundle', NULL),
      'related' => $field_name,
      'target_entity' => $entity->reveal(),
    ];
    $denormalized = $this->normalizer->denormalize($input, NULL, 'api_json', $context);
    $this->assertSame($expected, $denormalized);
  }

  /**
   * Data provider for the denormalize test.
   *
   * @return array
   *   The data for the test method.
   */
  public function denormalizeProvider() {
    return [
      [
        ['data' => [['type' => 'lorem--dummy_bundle', 'id' => '4e6cb61d-4f04-437f-99fe-42c002393658']]],
        'field_dummy',
        [['bunny' => 42]],
      ],
      [
        ['data' => []],
        'field_dummy',
        [],
      ],
      [
        ['data' => NULL],
        'field_dummy_single',
        [],
      ],
    ];
  }

  /**
   * @covers ::denormalize
   * @dataProvider denormalizeInvalidResourceProvider
   */
  public function testDenormalizeInvalidResource($data, $field_name) {
    $context = [
      'resource_type' => new ResourceType('fake_entity_type', 'dummy_bundle', NULL),
      'related' => $field_name,
      'target_entity' => $this->prophesize(FieldableEntityInterface::class)->reveal(),
    ];
    $this->setExpectedException(BadRequestHttpException::class);
    $this->normalizer->denormalize($data, NULL, 'api_json', $context);
  }

  /**
   * Data provider for the denormalize test.
   *
   * @return array
   *   The input data for the test method.
   */
  public function denormalizeInvalidResourceProvider() {
    return [
      [
        [
          'data' => [
            [
              'type' => 'invalid',
              'id' => '4e6cb61d-4f04-437f-99fe-42c002393658',
            ],
          ],
        ],
        'field_dummy',
      ],
      [
        [
          'data' => [
            'type' => 'lorem',
            'id' => '4e6cb61d-4f04-437f-99fe-42c002393658',
          ],
        ],
        'field_dummy',
      ],
      [
        [
          'data' => [
            [
              'type' => 'lorem',
              'id' => '4e6cb61d-4f04-437f-99fe-42c002393658',
            ],
          ],
        ],
        'field_dummy_single',
      ],
    ];
  }

}
