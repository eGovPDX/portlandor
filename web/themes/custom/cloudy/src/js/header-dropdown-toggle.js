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
      $(context)
        .find(".cloudy-header__menu-wrapper")
        .once("open-button-text")
        .on("show.bs.collapse", function() {
          const $textWrapper = $(".toggle__label");
          const $close_label = '<i class="fa fa-times m-0"></i>';

          $textWrapper.html($close_label);

          $(".collapse.show").each(function() {
            $(this)
              .collapse("hide");
          });
        });
    }
  };

  // on menu close show "Menu" text
  Drupal.behaviors.cloudyHeaderMenuToggleCloseText = {
    attach: function(context, settings) {
      $(context)
        .find(".cloudy-header__menu-wrapper")
        .once("close-button-text")
        .on("hide.bs.collapse", function() {
          const $textWrapper = $(".toggle__label");
          const $default_label = Drupal.t("Menu");

          $textWrapper.html($default_label);
        });
    }
  };

  // hide all open elements before showing closed toggled element
  Drupal.behaviors.cloudyHeaderHideCurrentlyOpen = {
    attach: function(context, settings) {
      $(context)
        .find(".collapse")
        .once("hide-open")
        .on("show.bs.collapse", function() {
          $(".collapse").each(function() {
            if ($(this).hasClass("show")) {
              $(this)
                .toggleClass("no-transition")
                .collapse("hide");
            }
            $(this).removeClass("no-transition");
          });
        });
    }
  };
})(jQuery, Drupal);
