(function ($, Drupal, formSettings, L) {

  Drupal.behaviors.locationPicker = {
    attach: (context) => {
      if (context === document) {
        // Create an instance of the controller and interact with it as needed.
        const controller = new LocationPickerController();
        const model = new LocationPickerModel(formSettings);

        var message = controller.getTestMessage(" -- and also this message from the behaviors!");
        alert(message);
      }
    }
  };

})(jQuery, Drupal, drupalSettings, L);


