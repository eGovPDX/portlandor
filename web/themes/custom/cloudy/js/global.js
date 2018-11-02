/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.bootstrap_barrio_subtheme = {
    attach: function (context, settings) {

      $('.page-node-1 .carousel').wrap('<div class="col-12 col-md-10 col-lg-8" />');
      $('.path-node .node--type-page').addClass('row');

      var classes = ['random1','random2', 'random3']; //add as many classes as u want
      var randomnumber = Math.floor(Math.random()*classes.length);
      $('body').addClass(classes[randomnumber]);

      $('.path-galerias #block-sass-starterkit-content').append("<div id='blueimp-gallery' class='blueimp-gallery'><div class='slides' /><h3 class='title' /><a class='prev'>‹</a><a class='next'>›</a><a class='close'>×</a><a class='play-pause' /><ol class='indicator' /></div>");
      $('.view-galerias').attr('id', 'links');

      $('.triple .view-content').addClass('ml-sm-1 mr-sm-1');

      var links = document.getElementById('links');
      if (links) {
        links.onclick = function (event) {
            event = event || window.event;
            var target = event.target || event.srcElement,
                link = target.src ? target.parentNode : target,
                options = {index: link, event: event},
                links = this.getElementsByTagName('a');
            blueimp.Gallery(links, options);
        };
      }
    }
  };

  Drupal.behaviors.tab_handler = {
    attach: function (context, settings) {

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

      $('#serviceModes a.nav-link').click(function (event) {
        event.preventDefault();
        // activate clicked tab using the link href
        selectTab($(this).attr("href"), $(this));
      });

      $('#serviceModes').keydown(function(event) {
        if (event.which == 39) {
          // focus tab to the right, if it exists
          var foundNext = focusedTab.parent().next().find('a');
          if (foundNext.attr('id')) {
            focusedTab = foundNext;
            focusedTab.focus();
          }
        } else if (event.which == 37) {
          // focus tab to the left
          var foundPrev = focusedTab.parent().prev().find('a');
          if (foundPrev.attr('id')) {
            focusedTab = foundPrev;
            focusedTab.focus();
          }
        }
      });

      function getSelectedTabIndex(key) {
        // search key for hyphen, and use rest of string as index. 
        // we are assuming key will always be in format #tab-1 or #pane-1
        var idx = key.indexOf("-");
        return parseInt(key.substring(idx+1, key.length));
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

})(jQuery, Drupal);

