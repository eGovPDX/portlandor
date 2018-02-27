<?php

namespace Drupal\media_entity_instagram;

/**
 * Defines a wrapper around the Instagram oEmbed call.
 */
interface InstagramEmbedFetcherInterface {

  /**
   * Retrieves a instagram post by its shortcode.
   *
   * @param int $shortcode
   *   The instagram post shortcode.
   * @param bool $hidecaption
   *   Indicates if the caption should be hidden in the html.
   * @param bool $maxWidth
   *   Max width of the instagram widget.
   *
   * @return array
   *   The instagram oEmbed information.
   */
  public function fetchInstagramEmbed($shortcode, $hidecaption = FALSE, $maxWidth = NULL);

}
