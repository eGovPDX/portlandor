import $ from 'jquery';
import Drupal from 'Drupal';

const COOKIE_PREFIX = 'Drupal.visitor.cloudy_notification_dismissed.';

Drupal.behaviors.notificatin_handler = {
  /**
   * @param {HTMLElement} context
   * @param settings
   */
  attach(context, settings) {
    // Compare each server side alert changed time with browser cookie values.
    // If the changed time doesn't match for that alert, display the alert.
    $('.cloudy-notification').once('alert-processed').each(function() {
      // If this alert has no nid it is not dismissible and did not set a cookie
      if (!$(this).data('nid')) return;

      const nid = $(this).data('nid');
      const cookieChangedTimestamp = $.cookie(COOKIE_PREFIX + nid);
      const alertChangedTimestamp = $(this).data('changed');
      if (cookieChangedTimestamp != alertChangedTimestamp) {
        // Only show the alert if dismiss button has not been clicked. The
        // element is hidden by default in order to prevent it from momentarily
        // flickering onscreen.
        $(this).addClass('cloudy-notification--active-dismissible');
      }
    });

    // Set the cookie value when dismiss button is clicked.
    $('.cloudy-notification__close').click(function (event) {
      event.preventDefault();
      const alertElement = $(this).closest('.cloudy-notification');

      // Hide the alert
      alertElement.removeClass('cloudy-notification--active-dismissible');

      // Set an alert cookie
      const nid = alertElement.data('nid');
      const lastChangedTimestamp = alertElement.data('changed');
      const path = (drupalSettings && drupalSettings.path && drupalSettings.path.baseUrl) || '/';
      $.cookie(
        COOKIE_PREFIX + nid,
        lastChangedTimestamp,
        {
          path,
        }
      );
    });
  }
};
