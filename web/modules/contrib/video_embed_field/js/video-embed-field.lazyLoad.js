/**
 * @file
 * The video_embed_field lazy loading videos.
 */

(function($) {
  Drupal.behaviors.video_embed_field_lazyLoad = {
    attach: function (context, settings) {
      $('.video-embed-field-lazy', context).once().click(function(e) {
        // Swap the lightweight image for the heavy JavaScript.
        e.preventDefault();
        var $el = $(this);
        $el.html($el.data('video-embed-field-lazy'));
      });
    }
  };
})(jQuery);
