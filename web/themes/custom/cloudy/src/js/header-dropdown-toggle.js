/**
 * @file
 * header global menu and search form Functionality.
 *
 * provides global menu and search input toggle functionality.
 * Used in global header.
 */
(function($, Drupal) {
  // on menu open show close icon
  Drupal.behaviors.cloudyHeaderMenuToggleOpenText = {
    attach: function(context, settings) {
      $(once('open-button-text', '.cloudy-header__menu-wrapper', context)).each(function () {
        $(this).on('show.bs.collapse', function() {
          $(".cloudy-header__toggle--menu .toggle-icon").removeClass("icon-menu").addClass("icon-close");

          $(".collapse.show").each(function() {
            $(this).collapse("hide");
          });
        });
      });
    }
  };

  // on menu close show menu icon
  Drupal.behaviors.cloudyHeaderMenuToggleCloseText = {
    attach: function(context, settings) {
      $(once('close-button-text', '.cloudy-header__menu-wrapper', context)).each(function () {
        $(this).on('hide.bs.collapse', function() {
          $(".cloudy-header__toggle--menu .toggle-icon").removeClass("icon-close").addClass("icon-menu");
        });
      });
    }
  };

  // hide all open elements before showing closed toggled element
  Drupal.behaviors.cloudyHeaderHideCurrentlyOpen = {
    attach: function(context, settings) {
      $(once('hide-open', '.collapse', context)).each(function () {
        $(this).on('show.bs.collapse', function() {
          $(".collapse").each(function() {
            if ($(this).hasClass("show")) {
              $(this)
                .toggleClass("no-transition")
                .collapse("hide");
            }
            $(this).removeClass("no-transition");
          });
        });
      });
    }
  };
})(jQuery, Drupal);
