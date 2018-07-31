/**
 * @file
 * Custom JS to handle alerts
 *
 */
(function ($, Drupal, drupalSettings) {

    'use strict';

    Drupal.behaviors.portland = {
      attach: function (context, settings) {
        // The server puts all publicshed alerts in drupalSettings.portland_alert.NID
        // The client has all previously dismissed alerts in cookies Drupal.visitor.portland_alert_dismissed.NID
        // Delete the cookies that doesn't have a matching server alert NID.
        var allCookies = $.cookie();
        var prefixLength = 'Drupal.visitor.portland_alert_dismissed.'.length;
        for(var cookieName in allCookies) {
          var nid = cookieName.substring(prefixLength);
          if(nid.length === 0) continue;
          if( ! drupalSettings.portland_alert.hasOwnProperty(nid) ) {
            $.removeCookie(cookieName);
          }
        }
        // Compare each server side alert changed time with browser cookie values.
        // If the changed time doesn't match for that alert, display the alert.
        // This is a map of { nid: changed }
        for(var nid in drupalSettings.portland_alert) {
          // Only show the alert if dismiss button has not been clicked. The
          // element is hidden by default in order to prevent it from momentarily
          // flickering onscreen.
          if ($.cookie('Drupal.visitor.portland_alert_dismissed.' + nid) !==
              drupalSettings.portland_alert[nid].changed) {
            $('[data-nid="'+ nid +'"]').closest('.portland-alert').css('display', 'block');
          }
        }

        // Set the cookie value when dismiss button is clicked.
        $('.portland-alert .close').click(function(e) {
          // Hide this alert
          $(this).closest('.portland-alert').css('display', 'none');

          // Do not perform default action.
          e.preventDefault();

          // Find the nid and changed time, set a cookie.
          if(!this.previousElementSibling) {
            console.error('Cannot find title node');
            return;
          }

          var linkedTitleNode = $(this.previousElementSibling);
          if(linkedTitleNode.data('nid')) {
            // Set cookie to the current key.
            $.cookie('Drupal.visitor.portland_alert_dismissed.' + linkedTitleNode.data('nid'),
            linkedTitleNode.data('changed'),
                  {
                    //expires: 180, // expires in 180 days
                    path: drupalSettings.path.baseUrl
                  });
          }
        });
      }
    };

  })(jQuery, Drupal, drupalSettings);
