(function ($, Drupal, drupalSettings, L) {

  Drupal.behaviors.locationPicker = {
    attach: (context) => {

      $(once('location_picker', 'fieldset.portland-location-picker--wrapper', context)).each(function () {
        if (context === document) {
          // Create an instance of the controller and interact with it as needed.
          var test = drupalSettings;
          const controller = new LocationPickerController();
          //const model = new LocationPickerModel(formSettings);

          //var message = controller.getTestMessage(" -- and also this message from the behaviors!");
          //alert(message);
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings, L);


