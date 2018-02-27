<?php

namespace Drupal\Tests\jsonapi\Kernel\Normalizer;

use Drupal\KernelTests\KernelTestBase;
use Drupal\jsonapi\Query\Sort;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\SortNormalizer
 * @group jsonapi
 * @group jsonapi_normalizers
 * @group legacy
 */
class SortNormalizerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'serialization',
    'system',
    'jsonapi',
  ];

  /**
   * The filter denormalizer.
   *
   * @var \Symfony\Component\Serializer\Normalizer\DenormalizerInterface
   */
  protected $normalizer;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->normalizer = $this->container->get('serializer.normalizer.sort.jsonapi');
  }

  /**
   * @covers ::denormalize
   * @dataProvider denormalizeProvider
   */
  public function testDenormalize($input, $expected) {
    $sort = $this->normalizer->denormalize($input, Sort::class);
    foreach ($sort->fields() as $index => $sort_field) {
      $this->assertEquals($expected[$index]['path'], $sort_field['path']);
      $this->assertEquals($expected[$index]['direction'], $sort_field['direction']);
      $this->assertEquals($expected[$index]['langcode'], $sort_field['langcode']);
    }
  }

  /**
   * Provides a suite of shortcut sort pamaters and their expected expansions.
   */
  public function denormalizeProvider() {
    return [
      ['lorem', [['path' => 'lorem', 'direction' => 'ASC', 'langcode' => NULL]]],
      ['-lorem', [['path' => 'lorem', 'direction' => 'DESC', 'langcode' => NULL]]],
      ['-lorem,ipsum', [
        ['path' => 'lorem', 'direction' => 'DESC', 'langcode' => NULL],
        ['path' => 'ipsum', 'direction' => 'ASC', 'langcode' => NULL],
      ],
      ],
      ['-lorem,-ipsum', [
        ['path' => 'lorem', 'direction' => 'DESC', 'langcode' => NULL],
        ['path' => 'ipsum', 'direction' => 'DESC', 'langcode' => NULL],
      ],
      ],
      [[
        ['path' => 'lorem', 'langcode' => NULL],
        ['path' => 'ipsum', 'langcode' => 'ca'],
        ['path' => 'dolor', 'direction' => 'ASC', 'langcode' => 'ca'],
        ['path' => 'sit', 'direction' => 'DESC', 'langcode' => 'ca'],
      ], [
        ['path' => 'lorem', 'direction' => 'ASC', 'langcode' => NULL],
        ['path' => 'ipsum', 'direction' => 'ASC', 'langcode' => 'ca'],
        ['path' => 'dolor', 'direction' => 'ASC', 'langcode' => 'ca'],
        ['path' => 'sit', 'direction' => 'DESC', 'langcode' => 'ca'],
      ],
      ],
    ];
  }

  /**
   * @covers ::denormalize
   * @dataProvider denormalizeFailProvider
   * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   */
  public function testDenormalizeFail($input) {
    $sort = $this->normalizer->denormalize($input, Sort::class);
  }

  /**
   * Data provider for testDenormalizeFail.
   */
  public function denormalizeFailProvider() {
    return [
      [[['lorem']]],
      [''],
    ];
  }

}
