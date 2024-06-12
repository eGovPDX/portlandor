(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.addressVerifier = {
      attach: function (context) {

          $(once('address-verifier', '.portland-address-verifier--wrapper', context)).each(function () {

              var model = new AddressVerifierModel();
              var view = new AddressVerifierView(); // Instantiating AddressVerifierView
              var controller = new AddressVerifierController(model, view);
              controller.init();

          });

      }
  };
})(jQuery, Drupal, drupalSettings);
