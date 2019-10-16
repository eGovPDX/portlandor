/**
 * @file
 * The media_embed_field colorbox integration.
 */

(function($) {
  Drupal.behaviors.media_embed_field_colorbox = {
    attach: function (context, settings) {
      $('.media-embed-field-launch-modal', context).once().click(function(e) {
        // Allow the thumbnail that launches the modal to link to other places
        // such as media URL, so if the modal is sidestepped things degrade
        // gracefully.
        e.preventDefault();
        $.colorbox($.extend(settings.colorbox, {'html': $(this).data('media-embed-field-modal')}));
      });
    }
  };
})(jQuery);
