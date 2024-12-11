(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.addressVerifier = {
      attach: function (context) {
        $(once('address-verifier', '.portland-address-verifier--wrapper', context)).each(function () {
          const $element = $(this);
          const elementId =  $element.attr('id');
  
          const apiKey = drupalSettings.portlandmaps_api_key;
  
          // Always initialize the model, view, and controller regardless of visibility
          const model = new AddressVerifierModel($, $element, apiKey);
          const view = new AddressVerifierView($, $element, model, drupalSettings.webform.portland_address_verifier[elementId]);
          const controller = new AddressVerifierController($element, model, view);
          controller.init();
        });
      }
    };
  })(jQuery, Drupal, drupalSettings);
  