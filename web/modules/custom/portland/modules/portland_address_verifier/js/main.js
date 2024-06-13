(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.addressVerifier = {
        attach: function (context) {

            $(once('address-verifier', '.portland-address-verifier--wrapper', context)).each(function () {

                var verifyButtonText = drupalSettings.webform ? drupalSettings.webform.portland_address_verifier.verify_button_text : "";
                var $element = $(this);

                var apiKey = drupalSettings.portlandmaps_api_key;

                var model = new AddressVerifierModel($, $element, apiKey);
                var view = new AddressVerifierView($, $element, model, drupalSettings.webform.portland_address_verifier); // Instantiating AddressVerifierView
                var controller = new AddressVerifierController($element, model, view);
                controller.init();

            });

        }
    };
})(jQuery, Drupal, drupalSettings);
