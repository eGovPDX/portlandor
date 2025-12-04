(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.customApiLookup = {
    attach: function (context, settings) {

      // Typical use case will be to use the location widget's X and Y fields
      const SOURCE1_SELECTOR = '[id="location_x"]';
      const SOURCE2_SELECTOR = '[id="location_y"]';

      // Customize this to match your target field
      const TARGET1_SELECTOR = '[id="hidden_base_zone"]';

      const API_URL = 'https://www.portlandmaps.com/api/detail/'; // <-- API base URL
      const API_KEY = 'AC3208DDEFB2FD0AE5F26D573C27252F'; // <-- your API key
      const POLL_INTERVAL = 500; // ms
      const LOOKUP_DEBOUNCE = 300; // ms

      // Look at both wizard pages and plain forms in this context.
      $(context).find('.js-webform-wizard-page, form.webform-submission-form').each(function () {
        const $container = $(this);

        // If this is a form that contains wizard pages, skip it and let the
        // page-level containers handle things. This avoids double init.
        if ($container.is('form') && $container.find('.js-webform-wizard-page').length) {
          return;
        }

        // Find the sources *inside this container*.
        const $source1 = $container.find(SOURCE1_SELECTOR);
        const $source2 = $container.find(SOURCE2_SELECTOR);

        // If this container doesn't have BOTH, we don't care about it.
        if (!$source1.length || !$source2.length) {
          return;
        }

        // Prevent double-initializing on AJAX re-attach for the same container.
        if ($container.data('customApiLookupTemplateInitialized')) {
          return;
        }
        $container.data('customApiLookupTemplateInitialized', true);

        // customize this on a per-form basis as needed
        const $target1 = $container.find(TARGET1_SELECTOR);

        let lastSource1 = $source1.val();
        let lastSource2 = $source2.val();
        let lastQueryKey = null;
        let lookupTimeout = null;

        function runLookup() {
          const value1 = $source1.val();
          const value2 = $source2.val();

          // REQUIRE both values before we do anything.
          if (!value1 || !value2) {
            return;
          }

          const queryKey = `${value1}|${value2}`;
          if (queryKey === lastQueryKey) {
            return; // No change since last lookup
          }
          lastQueryKey = queryKey;

          const url = `${API_URL}?detail_type=zoning&geometry={"x":${value1},"y":${value2}}&api_key=${API_KEY}`;

          $.ajax({
            url: url,
            method: 'GET', // or POST
            success: function (response) {
              if (!response) {
                return;
              }

              // Map response → target fields. Adjust keys to match your JSON.
              if ($target1.length && typeof response.zoning.base !== 'undefined') {
                $target1.val(response.zoning.base[0].code).trigger('change');
              }
            },
            error: function (xhr, status, error) {
              console.error('customApiLookup: API call failed', status, error);
            }
          });

        }

        // Debounced wrapper so rapid changes don’t spam the API.
        function scheduleLookup() {
          if (lookupTimeout) {
            clearTimeout(lookupTimeout);
          }
          lookupTimeout = setTimeout(runLookup, LOOKUP_DEBOUNCE);
        }

        // Polling, because the composite doesn't fire change events.
        const intervalId = window.setInterval(function () {
          // If the container has been removed (wizard step changed, form gone), stop.
          if (!$.contains(document, $container[0])) {
            window.clearInterval(intervalId);
            return;
          }

          const current1 = $source1.val();
          const current2 = $source2.val();

          if (current1 !== lastSource1 || current2 !== lastSource2) {
            lastSource1 = current1;
            lastSource2 = current2;

            // Only schedule lookup when both are non-empty.
            if (current1 && current2) {
              scheduleLookup();
            }
          }
        }, POLL_INTERVAL);

        $container.data('customApiLookupTemplateInterval', intervalId);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
