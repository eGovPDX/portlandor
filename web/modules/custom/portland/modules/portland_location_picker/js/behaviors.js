(function ($, Drupal, drupalSettings, L) {

  Drupal.behaviors.locationPicker = {
    attach: (context) => {

      $(once('location_picker', 'fieldset.portland-location-picker--wrapper', context)).each(function () {
        if (context === document) {
          // Create an instance of the controller and interact with it as needed.
          var test = drupalSettings;
          const controller = new LocationPickerController();
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings, L);


