(function ($, Drupal, drupalSettings) {

  /**
   * Attach the machine-readable name form element behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches machine-name behaviors.
   */
  Drupal.behaviors.portland_webform_address_verifier = {
    attach: function (context) {

      var test = $('main');
      var initialized;

      $(once('address_verifier', 'fieldset.portland-webform-address-verifier--wrapper', context)).each(function () {

        // CUSTOM PROPERTIES SET IN WEBFORM CONFIG //////////
        var useAddressLine2 = drupalSettings.webform ? drupalSettings.webform.portland_webform_address_verifier.use_address_line2 : "";
        var defaultState = drupalSettings.webform ? drupalSettings.webform.portland_webform_address_verifier.default_state : "";
        var verifyButtonText = drupalSettings.webform ? drupalSettings.webform.portland_webform_address_verifier.verify_button_text : "";

        // initialize widget
        initialized = initialize();

        function initialize() {

          // set state default value; for some reason it's not setting properly using the custom code in the element PHP
          $('#address_state').val(defaultState);

          // attach verify button to 
          $('#address_singleline').after(`<input class="button button--primary address-verify js-form-submit form-submit" type="button" id="address_verify" name="op" value="${verifyButtonText}">`);

          return true;
        }

      });
    }
  };
})(jQuery, Drupal, drupalSettings);
