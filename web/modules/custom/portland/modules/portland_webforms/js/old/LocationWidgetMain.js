(function ($, Drupal, drupalSettings, L) {
  Drupal.behaviors.portland_location_widget = {
    attach: function (context) {
      $(once('location-widget', 'fieldset.portland-location-widget--wrapper', context)).each(function () {

        console.log("LocationWidgetMain is plugged in");

        const element = this;
        const model = new LocationWidgetModel($, element, drupalSettings.apiKey);
        const view = new LocationWidgetView($, element, model, drupalSettings, L);
        const controller = new LocationWidgetController(element, model, view);

        controller.init();
      });
    }
  };
})(jQuery, Drupal, drupalSettings, L);
