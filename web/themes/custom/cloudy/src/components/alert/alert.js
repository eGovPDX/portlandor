import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.alert_handler = {
  /**
   * @param {HTMLElement} context
   * @param settings
   */
  attach(context, settings) {
    var COOKIE_PREFIX = 'Drupal.visitor.portland_alert_dismissed.';

    // Compare each server side alert changed time with browser cookie values.
    // If the changed time doesn't match for that alert, display the alert.
    $('.portland-alert').once('alert-processed').each(function() {
      if (!$(this).data('nid')) return;

      var nid = $(this).data('nid');
      if ($.cookie(COOKIE_PREFIX + nid) != $(this).data('changed')) {
        // Only show the alert if dismiss button has not been clicked. The
        // element is hidden by default in order to prevent it from momentarily
        // flickering onscreen.
        // use bootstrap classes
        $(this).removeClass('d-none');
      }
    });

    // Set the cookie value when dismiss button is clicked.
    $('.portland-alert .cloudy-alert__close').click(function (e) {
      // Do not perform default action.
      e.preventDefault();

      // Remove this alert
      var alertElement = $(this).closest('.portland-alert')
      alertElement.addClass('d-none');

      var nid;
      if (nid === alertElement.data('nid')) {
        const base = drupalSettings.path.baseUrl || '/';
        // Set cookie to the current key.
        $.cookie(
          COOKIE_PREFIX + nid,
          alertElement.data('changed'),
          {
            path: base
          }
        );
      }
    });
  }
};
