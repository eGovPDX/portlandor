// Import all of the bootstrap javascript components
import 'bootstrap';

// Set up handlers for behaviors across all pages
(function($, Drupal) {
  'use strict';

  Drupal.behaviors.tab_handler = {
    attach: function(context, settings) {
      var urlHash = window.location.hash;
      var selectedTabIndex = 0;
      var selectedTab;
      var focusedTab;

      // on initial load, check for tab navigation fragment in URL and activate indicated tab
      if (urlHash.indexOf('#pane-') > -1) {
        selectedTabIndex = getSelectedTabIndex(urlHash);
        selectedTab = $('#tab-' + selectedTabIndex);
        selectedTab.tab('show');
        selectTab(urlHash, selectedTab);
      }

      $('#serviceModes a.nav-link').click(function(event) {
        event.preventDefault();
        // activate clicked tab using the link href
        selectTab($(this).attr('href'), $(this));
      });

      $('#serviceModes').keydown(function(event) {
        if (event.which == 39) {
          // focus tab to the right, if it exists
          var foundNext = focusedTab
            .parent()
            .next()
            .find('a');
          if (foundNext.attr('id')) {
            focusedTab = foundNext;
            focusedTab.focus();
          }
        } else if (event.which == 37) {
          // focus tab to the left
          var foundPrev = focusedTab
            .parent()
            .prev()
            .find('a');
          if (foundPrev.attr('id')) {
            focusedTab = foundPrev;
            focusedTab.focus();
          }
        }
      });

      function getSelectedTabIndex(key) {
        // search key for hyphen, and use rest of string as index.
        // we are assuming key will always be in format #tab-1 or #pane-1
        var idx = key.indexOf('-');
        return parseInt(key.substring(idx + 1, key.length));
      }

      function selectTab(linkHash, tab) {
        // add fragment to url; use replaceState to avoid filling up the history with tab changes;
        // we are not supporting browser navigation between tabs/url fragments at this time
        if (history.replaceState) {
          history.replaceState(null, null, linkHash);
        } else {
          location.hash = linkHash;
        }

        // toggle tabindex; should be -1 for all non-selected tabs for accessibility purposes
        $('#serviceModes a.nav-link').attr('tabindex', -1);
        tab.removeAttr('tabindex');

        // aria-hidden should be true for all but visible pane; bootstrap doesn't handle this
        $('#serviceModesContent .tab-pane').attr('aria-hidden', 'true');
        var panel = $(linkHash);
        panel.attr('aria-hidden', false);

        // focus the active tab; necessary in the case of page reload or direct navigation
        tab.focus();
        focusedTab = tab;
      }
    }
  };

  Drupal.behaviors.dialog_handler = {
    attach: function(context, settings) {
      $(window).on('dialogcreate', function(e, dialog) {
        $('body')
          .find('.ui-dialog-titlebar-close')
          .once('fa-close-added')
          .each(function() {
            $(this).append('<i class="fa fa-window-close"></i>');
          });
      });
    }
  };


  Drupal.behaviors.alert_handler = {
    attach: function (context, settings) {
      var COOKIE_PREFIX = 'Drupal.visitor.portland_alert_dismissed.';

      // Compare each server side alert changed time with browser cookie values.
      // If the changed time doesn't match for that alert, display the alert.
      $('.portland-alert').once('alert-processed').each(function() {
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
})(jQuery, Drupal);
