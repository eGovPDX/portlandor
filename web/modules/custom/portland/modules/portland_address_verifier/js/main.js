(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.addressVerifier = {
    attach: function (context) {
      $(once('address-verifier', '.portland-address-verifier--wrapper', context)).each(function () {
        const $element = $(this);
        const elementId = $element.attr('id').replace(/--[^-]+--wrapper$/, '--wrapper');

        const apiKey = drupalSettings.portlandmaps_api_key;

        const model = new AddressVerifierModel($, $element, apiKey);
        const view = new AddressVerifierView($, $element, model, drupalSettings.webform.portland_address_verifier[elementId]);
        model.view = view;
        const controller = new AddressVerifierController($element, model, view);
        controller.init();

        // ✅ Expose the view to the DOM for the postback behavior to use
        $element[0].addressVerifierInstance = view;
      });
    }
  };

  // Postback-safe hook that runs AFTER Webform rendering and state updates
  Drupal.behaviors.addressVerifierPostback = {
    attach: function (context, settings) {
      $(once('address-verifier-postback', '.portland-address-verifier--wrapper', context)).each(function () {
        if (this.addressVerifierInstance) {
          this.addressVerifierInstance._handlePostback();
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
