/**
 * @file
 * The media_embed_field lazy loading media.
 */

(function($) {
  Drupal.behaviors.media_embed_field_lazyLoad = {
    attach: function (context, settings) {
      $('.media-embed-field-lazy', context).once().click(function(e) {
        // Swap the lightweight image for the heavy JavaScript.
        e.preventDefault();
        var $el = $(this);
        $el.html($el.data('media-embed-field-lazy'));
      });
    }
  };
})(jQuery);
