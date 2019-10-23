<?php

namespace Drupal\Tests\media_embed_field\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\media_embed_field\Plugin\Field\FieldFormatter\Thumbnail;

/**
 * Test the embed field formatters are functioning.
 *
 * @group media_embed_field
 */
class FieldOutputTest extends KernelTestBase {

  use StripWhitespaceTrait;

  /**
   * The test cases.
   */
  public function renderedFieldTestCases() {
    return [
      'YouTube: Thumbnail' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw',
        [
          'type' => 'media_embed_field_thumbnail',
          'settings' => [],
        ],
        [
          '#theme' => 'image',
          '#uri' => 'public://media_thumbnails/fdbFVWupSsw.jpg',
        ],
      ],
      'YouTube: Thumbnail With Image Style' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw',
        [
          'type' => 'media_embed_field_thumbnail',
          'settings' => [
            'image_style' => 'thumbnail',
          ],
        ],
        [
          '#theme' => 'image_style',
          '#uri' => 'public://media_thumbnails/fdbFVWupSsw.jpg',
          '#style_name' => 'thumbnail',
        ],
      ],
      'YouTube: Embed Code' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw',
        [
          'type' => 'media_embed_field_media',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'media-embed-field-provider-youtube',
            ],
          ],
          'children' => [
            '#type' => 'media_embed_iframe',
            '#provider' => 'youtube',
            '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
            '#query' => [
              'autoplay' => '1',
              'start' => '0',
              'rel' => '0',
            ],
            '#attributes' => [
              'width' => '100',
              'height' => '100',
              'frameborder' => '0',
              'allowfullscreen' => 'allowfullscreen',
            ],
            '#cache' => [
              'contexts' => [
                'user.permissions',
              ],
            ],
          ],
        ],
      ],
      'YouTube: Time-index Embed Code' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw&t=100',
        [
          'type' => 'media_embed_field_media',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'media-embed-field-provider-youtube',
            ],
          ],
          'children' => [
            '#type' => 'media_embed_iframe',
            '#provider' => 'youtube',
            '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
            '#query' => [
              'autoplay' => '1',
              'start' => '100',
              'rel' => '0',
            ],
            '#attributes' => [
              'width' => '100',
              'height' => '100',
              'frameborder' => '0',
              'allowfullscreen' => 'allowfullscreen',
            ],
            '#cache' => [
              'contexts' => [
                'user.permissions',
              ],
            ],
          ],
        ],
      ],
      'YouTube: Language Specified Embed Code' => [
        'https://www.youtube.com/watch?v=fdbFVWupSsw&hl=fr',
        [
          'type' => 'media_embed_field_media',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'media-embed-field-provider-youtube',
            ],
          ],
          'children' => [
            '#type' => 'media_embed_iframe',
            '#provider' => 'youtube',
            '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
            '#query' => [
              'autoplay' => '1',
              'start' => '0',
              'rel' => '0',
              'cc_lang_pref' => 'fr',
            ],
            '#attributes' => [
              'width' => '100',
              'height' => '100',
              'frameborder' => '0',
              'allowfullscreen' => 'allowfullscreen',
            ],
            '#cache' => [
              'contexts' => [
                'user.permissions',
              ],
            ],
          ],
        ],
      ],
      'Vimeo: Thumbnail' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'media_embed_field_thumbnail',
          'settings' => [],
        ],
        [
          '#theme' => 'image',
          '#uri' => 'public://media_thumbnails/80896303.jpg',
        ],
      ],
      'Vimeo: Embed Code' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'media_embed_field_media',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'media-embed-field-provider-vimeo',
            ],
          ],
          'children' => [
            '#type' => 'media_embed_iframe',
            '#provider' => 'vimeo',
            '#url' => 'https://player.vimeo.com/video/80896303',
            '#query' => [
              'autoplay' => '1',
            ],
            '#attributes' => [
              'width' => '100',
              'height' => '100',
              'frameborder' => '0',
              'allowfullscreen' => 'allowfullscreen',
            ],
            '#cache' => [
              'contexts' => [
                'user.permissions',
              ],
            ],
          ],
        ],
      ],
      'Vimeo: Autoplaying Embed Code' => [
        'https://vimeo.com/80896303#t=150s',
        [
          'type' => 'media_embed_field_media',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'media-embed-field-provider-vimeo',
            ],
          ],
          'children' => [
            '#type' => 'media_embed_iframe',
            '#provider' => 'vimeo',
            '#url' => 'https://player.vimeo.com/video/80896303',
            '#query' => [
              'autoplay' => '1',
            ],
            '#fragment' => 't=150s',
            '#attributes' => [
              'width' => '100',
              'height' => '100',
              'frameborder' => '0',
              'allowfullscreen' => 'allowfullscreen',
            ],
            '#cache' => [
              'contexts' => [
                'user.permissions',
              ],
            ],
          ],
        ],
      ],
      'Linked Thumbnail: Content' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'media_embed_field_thumbnail',
          'settings' => ['link_image_to' => Thumbnail::LINK_CONTENT],
        ],
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => 'public://media_thumbnails/80896303.jpg',
          ],
          '#url' => 'entity.entity_test.canonical',
        ],
      ],
      'Linked Thumbnail: Provider' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'media_embed_field_thumbnail',
          'settings' => ['link_image_to' => Thumbnail::LINK_PROVIDER],
        ],
        [
          '#type' => 'link',
          '#title' => [
            '#theme' => 'image',
            '#uri' => 'public://media_thumbnails/80896303.jpg',
          ],
          '#url' => 'https://vimeo.com/80896303',
        ],
      ],
      'Colorbox Modal: Linked Image & Autoplay' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'media_embed_field_colorbox',
          'settings' => [
            'link_image_to' => Thumbnail::LINK_PROVIDER,
            'autoplay' => TRUE,
            'width' => 500,
            'height' => 500,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'data-media-embed-field-modal' => '<div class="media-embed-field-provider-vimeo"><iframe width="500" height="500" frameborder="0" allowfullscreen="allowfullscreen" src="https://player.vimeo.com/video/80896303?autoplay=1"></iframe></div>',
            'class' => ['media-embed-field-launch-modal'],
          ],
          '#attached' => [
            'library' => [
              'media_embed_field/colorbox',
              'media_embed_field/responsive-media',
            ],
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
          'children' => [
            '#type' => 'link',
            '#title' => [
              '#theme' => 'image',
              '#uri' => 'public://media_thumbnails/80896303.jpg',
            ],
            '#url' => 'https://vimeo.com/80896303',
          ],
        ],
      ],
      'Colorbox Modal: Responsive' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'media_embed_field_colorbox',
          'settings' => [
            'link_image_to' => Thumbnail::LINK_PROVIDER,
            'autoplay' => TRUE,
            'width' => 900,
            'height' => 450,
            'responsive' => TRUE,
            'modal_max_width' => 999,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'data-media-embed-field-modal' => '<div class="media-embed-field-provider-vimeo media-embed-field-responsive-media media-embed-field-responsive-modal" style="width:999px;"><iframe width="900" height="450" frameborder="0" allowfullscreen="allowfullscreen" src="https://player.vimeo.com/video/80896303?autoplay=1"></iframe></div>',
            'class' => [
              'media-embed-field-launch-modal',
            ],
          ],
          '#attached' => [
            'library' => [
              'media_embed_field/colorbox',
              'media_embed_field/responsive-media',
            ],
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
          'children' => [
            '#type' => 'link',
            '#title' => [
              '#theme' => 'image',
              '#uri' => 'public://media_thumbnails/80896303.jpg',
            ],
            '#url' => 'https://vimeo.com/80896303',
          ],
        ],
      ],
      'Lazy load formatter' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'media_embed_field_lazyload',
          'settings' => [
            'link_image_to' => Thumbnail::LINK_PROVIDER,
            'autoplay' => TRUE,
            'width' => 900,
            'height' => 450,
            'responsive' => TRUE,
            'modal_max_width' => 999,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'data-media-embed-field-lazy' => '<div class="media-embed-field-provider-vimeo media-embed-field-responsive-media"><iframe width="900" height="450" frameborder="0" allowfullscreen="allowfullscreen" src="https://player.vimeo.com/video/80896303?autoplay=1"></iframe></div>',
            'class' => [
              'media-embed-field-lazy',
            ],
          ],
          '#attached' => [
            'library' => [
              'media_embed_field/lazy-load',
            ],
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
          'children' => [
            [
              '#type' => 'link',
              '#title' => [
                '#theme' => 'image',
                '#uri' => 'public://media_thumbnails/80896303.jpg',
              ],
              '#url' => 'https://vimeo.com/80896303',
            ],
            [
              '#type' => 'html_tag',
              '#tag' => 'button',
              '#attributes' => [
                'class' => [
                  'media-embed-field-lazy-play',
                ],
              ],
            ],
          ],
        ],
      ],
      'Media: Responsive' => [
        'https://vimeo.com/80896303',
        [
          'type' => 'media_embed_field_media',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => TRUE,
          ],
        ],
        [
          '#type' => 'container',
          '#attached' => [
            'library' => ['media_embed_field/responsive-media'],
          ],
          '#attributes' => [
            'class' => [
              'media-embed-field-provider-vimeo',
              'media-embed-field-responsive-media'
            ],
          ],
          'children' => [
            '#type' => 'media_embed_iframe',
            '#provider' => 'vimeo',
            '#url' => 'https://player.vimeo.com/video/80896303',
            '#query' => [
              'autoplay' => '1',
            ],
            '#attributes' => [
              'width' => '100',
              'height' => '100',
              'frameborder' => '0',
              'allowfullscreen' => 'allowfullscreen',
            ],
            '#cache' => [
              'contexts' => [
                'user.permissions',
              ],
            ],
          ],
        ],
      ],
      'YouTube Playlist' => [
        'https://www.youtube.com/watch?v=xoJH3qZwsHc&list=PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
        [
          'type' => 'media_embed_field_media',
          'settings' => [
            'width' => 100,
            'height' => 100,
            'autoplay' => TRUE,
            'responsive' => FALSE,
          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'media-embed-field-provider-youtube-playlist',
            ],
          ],
          'children' => [
            '#type' => 'media_embed_iframe',
            '#provider' => 'youtube_playlist',
            '#url' => 'https://www.youtube.com/embed/videoseries',
            '#query' => [
              'list' => 'PLpeDXSh4nHjQCIZmkxg3VSdpR5e87X5eB',
            ],
            '#attributes' => [
              'width' => '100',
              'height' => '100',
              'frameborder' => '0',
              'allowfullscreen' => 'allowfullscreen',
            ],
            '#cache' => [
              'contexts' => [
                'user.permissions',
              ],
            ],
          ],
        ],
      ],
      'No provider (media formatter)' => [
        'http://example.com/not/a/video/url',
        [
          'type' => 'media_embed_field_media',
          'settings' => [],
        ],
        [
          '#theme' => 'media_embed_field_missing_provider',
        ],
      ],
      'No provider (thumbnail formatter)' => [
        'http://example.com/not/a/video/url',
        [
          'type' => 'media_embed_field_thumbnail',
          'settings' => [],
        ],
        [
          '#theme' => 'media_embed_field_missing_provider',
        ],
      ],
      'No provider (colorbox modal)' => [
        'http://example.com/not/a/video/url',
        [
          'type' => 'media_embed_field_colorbox',
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'data-media-embed-field-modal' => 'No media provider was found to handle the given URL. See <a href="https://www.drupal.org/node/2842927">the documentation</a> for more information.',
            'class' => ['media-embed-field-launch-modal'],
          ],
          '#attached' => [
            'library' => [
              'media_embed_field/colorbox',
              'media_embed_field/responsive-media',
            ],
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
          'children' => [
            '#theme' => 'media_embed_field_missing_provider',
          ],
        ],
      ],
    ];
  }

  /**
   * Test the embed field.
   *
   * @dataProvider renderedFieldTestCases
   */
  public function testEmbedField($url, $settings, $expected_field_item_output) {

    $field_output = $this->getPreparedFieldOutput($url, $settings);

    // Assert the specific field output at delta 1 matches the expected test
    // data.
    $this->assertEquals($expected_field_item_output, $field_output[0]);
  }

  /**
   * Get and prepare the output of a field.
   *
   * @param string $url
   *   The media URL.
   * @param array $settings
   *   An array of formatter settings.
   *
   * @return array
   *   The rendered prepared field output.
   */
  protected function getPreparedFieldOutput($url, $settings) {
    $entity = EntityTest::create();
    $entity->{$this->fieldName}->value = $url;
    $entity->save();

    $field_output = $this->container->get('renderer')
      ->executeInRenderContext(new RenderContext(), function () use ($entity, $settings) {
        return $entity->{$this->fieldName}->view($settings);
      });

    // Prepare the field output to make it easier to compare our test data
    // values against.
    array_walk_recursive($field_output[0], function (&$value) {
      // Prevent circular references with comparing field output that
      // contains url objects.
      if ($value instanceof Url) {
        $value = $value->isRouted() ? $value->getRouteName() : $value->getUri();
      }
      // Trim to prevent stray whitespace for the colorbox formatters with
      // early rendering.
      $value = $this->stripWhitespace($value);
    });

    return $field_output;
  }

}
