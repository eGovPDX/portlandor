<?php

namespace Drupal\Tests\jsonapi\Kernel\Context;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\jsonapi\Kernel\JsonapiKernelTestBase;

/**
 * @coversDefaultClass \Drupal\jsonapi\Context\FieldResolver
 * @group jsonapi
 * @group legacy
 */
class FieldResolverTest extends JsonapiKernelTestBase {

  public static $modules = ['entity_test', 'serialization', 'field', 'text'];

  /**
   * @var \Drupal\jsonapi\Context\FieldResolver
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->sut = \Drupal::service('jsonapi.field_resolver');

    EntityTestBundle::create([
      'id' => 'bundle1',
    ])->save();
    EntityTestBundle::create([
      'id' => 'bundle2',
    ])->save();
    EntityTestBundle::create([
      'id' => 'bundle3',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_test1',
      'type' => 'string',
      'entity_type' => 'entity_test_with_bundle',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test1',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle1',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_test2',
      'type' => 'string',
      'entity_type' => 'entity_test_with_bundle',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test2',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle1',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_test3',
      'type' => 'string',
      'entity_type' => 'entity_test_with_bundle',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test3',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle2',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test3',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle3',
    ])->save();

    // Provides entity reference fields.
    FieldStorageConfig::create([
      'field_name' => 'field_test_ref1',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'entity_test_with_bundle',
      ],
      'entity_type' => 'entity_test_with_bundle',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test_ref1',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle1',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_test_ref2',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'entity_test_with_bundle',
      ],
      'entity_type' => 'entity_test_with_bundle',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test_ref2',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle1',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_test_ref3',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'entity_test_with_bundle',
      ],
      'entity_type' => 'entity_test_with_bundle',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test_ref3',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle2',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test_ref3',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle3',
    ])->save();

    // Add a field with multiple properties.
    FieldStorageConfig::create([
      'field_name' => 'field_test_text',
      'type' => 'text',
      'entity_type' => 'entity_test_with_bundle',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test_text',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle1',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test_text',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'bundle2',
    ])->save();
  }

  /**
   * @covers ::resolveInternal
   */
  public function testResolveInternal() {
    $entity_type_id = 'entity_test_with_bundle';
    $bundle = 'bundle1';

    $this->assertEquals('field_test1', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test1'));
    $this->assertEquals('field_test2', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test2'));
    $this->assertEquals('field_test3', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test3'));

    $this->assertEquals('field_test_ref1.entity.field_test1', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref1.field_test1'));
    $this->assertEquals('field_test_ref1.entity.field_test2', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref1.field_test2'));
    $this->assertEquals('field_test_ref2.entity.field_test1', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref2.field_test1'));
    $this->assertEquals('field_test_ref2.entity.field_test2', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref2.field_test2'));
    $this->assertEquals('field_test_ref3.entity.field_test1', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref3.field_test1'));
    $this->assertEquals('field_test_ref3.entity.field_test2', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref3.field_test2'));

    $this->assertEquals('field_test_ref1.entity.field_test_text', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref1.field_test_text'));
    $this->assertEquals('field_test_ref1.entity.field_test_text.value', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref1.field_test_text.value'));
    $this->assertEquals('field_test_ref1.entity.field_test_text.format', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref1.field_test_text.format'));
    $this->assertEquals('field_test_ref2.entity.field_test_text', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref2.field_test_text'));
    $this->assertEquals('field_test_ref2.entity.field_test_text.value', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref2.field_test_text.value'));
    $this->assertEquals('field_test_ref2.entity.field_test_text.format', $this->sut->resolveInternal($entity_type_id, $bundle, 'field_test_ref2.field_test_text.format'));
  }

  /**
   * Expects an error when an invalid field is provided.
   *
   * @covers ::resolveInternal
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   */
  public function testResolveInternalError() {
    $original = 'host.fail!!.deep';
    $not_expected = 'host.entity.fail!!.entity.deep';

    $this->assertEquals($not_expected, $this->sut->resolveInternal('entity_test_with_bundle', 'bundle1', $original));
  }

}
