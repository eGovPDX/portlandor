/**
 * @file
 * Custom JS to handle alerts
 *
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.portland = {
    attach: function (context, settings) {
      var COOKIE_PREFIX = 'Drupal.visitor.portland_alert_dismissed.';

      // Compare each server side alert changed time with browser cookie values.
      // If the changed time doesn't match for that alert, display the alert.
      Object.keys(drupalSettings.portland_alert).forEach(function (nid) {
        if ($.cookie(COOKIE_PREFIX + nid) !== drupalSettings.portland_alert[nid].changed) {
          // Only show the alert if dismiss button has not been clicked. The
          // element is hidden by default in order to prevent it from momentarily
          // flickering onscreen.
          var alertElement = $('[data-nid="' + nid + '"]');
          // use bootstrap classes
          alertElement.removeClass('d-none');
        }
      });

      // Set the cookie value when dismiss button is clicked.
      $('.portland-alert .close').click(function (e) {
        // Do not perform default action.
        e.preventDefault();

        // Remove this alert
        var alertElement = $(this).closest('.portland-alert')
        alertElement.addClass('d-none');

        var nid;
        if (nid = alertElement.data('nid')) {
          // Set cookie to the current key.
          $.cookie(
            COOKIE_PREFIX + nid,
            alertElement.data('changed'),
            {
              path: drupalSettings.path.baseUrl
            }
          );
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
