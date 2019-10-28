<?php

namespace Drupal\Tests\media_embed_field\Kernel;

/**
 * Test that the iframe element works.
 *
 * @group media_embed_field
 */
class MediaEmbedIFrameTest extends KernelTestBase {

  /**
   * Test cases for the embed iframe.
   *
   * @return array
   *   Media iframe test cases.
   */
  public function mediaEmbedIframeTestCases() {
    return [
      'Default' => [
        [
          '#type' => 'media_embed_iframe',
        ],
        '<iframe></iframe>',
      ],
      'URL' => [
        [
          '#type' => 'media_embed_iframe',
          '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
        ],
        '<iframe src="https://www.youtube.com/embed/fdbFVWupSsw"></iframe>',
      ],
      'URL, query' => [
        [
          '#type' => 'media_embed_iframe',
          '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
          '#query' => ['autoplay' => '1'],
        ],
        '<iframe src="https://www.youtube.com/embed/fdbFVWupSsw?autoplay=1"></iframe>',
      ],
      'URL, query, attributes' => [
        [
          '#type' => 'media_embed_iframe',
          '#url' => 'https://www.youtube.com/embed/fdbFVWupSsw',
          '#query' => ['autoplay' => '1'],
          '#attributes' => [
            'width' => '100',
          ],
        ],
        '<iframe width="100" src="https://www.youtube.com/embed/fdbFVWupSsw?autoplay=1"></iframe>',
      ],
      'Query' => [
        [
          '#type' => 'media_embed_iframe',
          '#query' => ['autoplay' => '1'],
        ],
        '<iframe></iframe>',
      ],
      'Query, attributes' => [
        [
          '#type' => 'media_embed_iframe',
          '#query' => ['autoplay' => '1'],
          '#attributes' => [
            'width' => '100',
          ],
        ],
        '<iframe width="100"></iframe>',
      ],
      'Attributes' => [
        [
          '#type' => 'media_embed_iframe',
          '#attributes' => [
            'width' => '100',
          ],
        ],
        '<iframe width="100"></iframe>',
      ],
      'Fragment' => [
        [
          '#type' => 'media_embed_iframe',
          '#url' => 'https://example.com',
          '#fragment' => 'test fragment',
        ],
        '<iframe src="https://example.com#test fragment"></iframe>',
      ],
      'XSS Testing' => [
        [
          '#type' => 'media_embed_iframe',
          '#attributes' => [
            'xss' => '">',
          ],
          '#query' => ['xss' => '">'],
          '#url' => '">',
          '#fragment' => '">',
        ],
        '<iframe xss="&quot;&gt;" src="&quot;&gt;?xss=%22%3E#&quot;&gt;"></iframe>',
      ],
    ];
  }

  /**
   * Test the media embed iframe renders correctly.
   *
   * @dataProvider mediaEmbedIframeTestCases
   */
  public function testMediaEmbedIframe($renderable, $markup) {
    $this->assertEquals($markup, trim($this->container->get('renderer')->renderRoot($renderable)));
  }

}
