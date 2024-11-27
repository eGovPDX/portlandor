(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.addressVerifier = {
        attach: function (context) {

            $(once('address-verifier', '.portland-address-verifier', context)).each(function (index, element) {

                var $element = $(this);
                const elementId = $(element).attr('id');

                var apiKey = drupalSettings.portlandmaps_api_key;

                var model = new AddressVerifierModel($, $element, apiKey);
                var view = new AddressVerifierView($, $element, model, drupalSettings.webform.portland_address_verifier[elementId]); // Instantiating AddressVerifierView
                var controller = new AddressVerifierController($element, model, view);
                controller.init();

            });

        }
    };
})(jQuery, Drupal, drupalSettings);
