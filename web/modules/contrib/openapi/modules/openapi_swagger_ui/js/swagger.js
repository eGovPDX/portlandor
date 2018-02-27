/**
 * @file
 * Provides Swagger integration.
 */

(function ($, Drupal, drupalSettings) {


  // SwaggerUI expects $ to be defined as the jQuery object.
  // @todo File a patch to Swagger UI to not require this.
  window.$ = $;

  Drupal.behaviors.swaggerui = {
    attach: function (context, settings) {
      var url = drupalSettings.openapi.json_url;
      /*
       hljs.configure({
       highlightSizeThreshold: 5000
       });
       */
      // Build a system
      const ui = SwaggerUIBundle({
        url: url,
        //dom_id: '#swagger-ui',
        dom_id: "#swagger-ui-container",
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "StandaloneLayout"
      });

      window.ui = ui;


    }
  };

})(jQuery, Drupal, drupalSettings);
