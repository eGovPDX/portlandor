import $ from 'jquery';
import Drupal from 'Drupal';

const COOKIE_PREFIX = 'Drupal.visitor.portland_alert_dismissed.';

Drupal.behaviors.alert_handler = {
  /**
   * @param {HTMLElement} context
   * @param settings
   */
  attach(context, settings) {
    // Compare each server side alert changed time with browser cookie values.
    // If the changed time doesn't match for that alert, display the alert.
    // @todo remove the '.portland-alert' selector below when the PL version of this component is implemented in drupal
    $('.portland-alert, .cloudy-alert').once('alert-processed').each(function() {
      // If this alert has no nid it is not dismissible and did not set a cookie
      if (!$(this).data('nid')) return;

      const nid = $(this).data('nid');
      const cookieChangedTimestamp = $.cookie(COOKIE_PREFIX + nid);
      const alertChangedTimestamp = $(this).data('changed');
      if (cookieChangedTimestamp != alertChangedTimestamp) {
        // Only show the alert if dismiss button has not been clicked. The
        // element is hidden by default in order to prevent it from momentarily
        // flickering onscreen.
        $(this).addClass('cloudy-alert--active-dismissible');

        // @todo remove the line below when the PL version of this component is implemented in drupal
        $(this).removeClass('d-none');
      }
    });

    // Set the cookie value when dismiss button is clicked.
    // @todo remove the '.portland-alert .close' selector below when the PL version of this component is implemented in drupal
    $('.cloudy-alert .cloudy-alert__close, .portland-alert .close').click(function (event) {
      event.preventDefault();
      // @todo remove the '.portland-alert' selector below when the PL version of this component is implemented in drupal
      const alertElement = $(this).closest('.portland-alert, .cloudy-alert');

      // Hide the alert
      alertElement.removeClass('cloudy-alert--active-dismissible');
      // @todo remove the line below when the PL version of this component is implemented in drupal
      alertElement.addClass('d-none');

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
