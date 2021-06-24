(function ($) {
  Drupal.behaviors.portland_location_picker = {
    attach: function (context, settings) {

      // insert module custom js here...
      alert('Portland Location Picker javascript is plugged in!');

      // $('.media-embed-field-launch-modal', context).once().click(function (e) {
      //   // Allow the thumbnail that launches the modal to link to other places
      //   // such as media URL, so if the modal is sidestepped things degrade
      //   // gracefully.
      //   e.preventDefault();
      //   $.colorbox($.extend(settings.colorbox, { 'html': $(this).data('media-embed-field-modal') }));
      // });

    }
  };
})(jQuery);
