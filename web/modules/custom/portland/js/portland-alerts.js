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
      // The server puts all published alerts in drupalSettings.portland_alert.NID
      // The client has all previously dismissed alerts in cookies Drupal.visitor.portland_alert_dismissed.NID
      // Delete the cookies that don't have a matching server alert NID.
      var regex = new RegExp('^' + COOKIE_PREFIX + '([0-9]+)$', 'g');
      Object.keys($.cookie()).forEach(function(cookie) {
        var match_array;
        if((match_array = regex.exec(cookie)) !== null) {
          var nid = match_array[1];
          if (!drupalSettings.portland_alert.hasOwnProperty(nid)) {
            $.removeCookie(cookie);
          }
        }
      });

      // Compare each server side alert changed time with browser cookie values.
      // If the changed time doesn't match for that alert, display the alert.
      Object.keys(drupalSettings.portland_alert).forEach(function(nid) {
        if ($.cookie(COOKIE_PREFIX + nid) !== drupalSettings.portland_alert[nid].changed) {
          // Only show the alert if dismiss button has not been clicked. The
          // element is hidden by default in order to prevent it from momentarily
          // flickering onscreen.
          var alertElement = $('[data-nid="' + nid + '"]').closest('.alert-dismissible');
          // use bootstrap classes
          alertElement.removeClass('d-none');
          alertElement.addClass('d-flex');
        }
      });

      // Set the cookie value when dismiss button is clicked.
      $('.alert-dismissible .close').click(function (e) {
        // Do not perform default action.
        e.preventDefault();

        // Remove this alert
        $(this).closest('.alert-dismissible').removeClass('d-flex').addClass('d-none');

        // Find the nid and changed time, set a cookie.
        if (!this.previousElementSibling) {
          console.error('Cannot find title node');
          return;
        }

        var linkedTitleNode = $(this.previousElementSibling);
        var nid;
        if (nid = linkedTitleNode.data('nid')) {
          // Set cookie to the current key.
          $.cookie(
            COOKIE_PREFIX + nid,
            linkedTitleNode.data('changed'),
            {
              path: drupalSettings.path.baseUrl
            }
          );
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
