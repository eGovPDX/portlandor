/**
 * @file
 * Global search placeholder Functionality.
 *
 * Hides search input placeholder text on focus change
 */
(function($, Drupal) {
  Drupal.behaviors.cloudyHideSearchPlaceholder = {
    attach: function(context, settings) {
      $(context)
        .find(".ui-autocomplete-input[data-search-api-autocomplete-search]")
        .once("search-clear")
        .on("focusin focusout", function() {
          const $this = $(this);
          $this.attr("placeholder") === "Search"
            ? $this.attr("placeholder", "")
            : $this.attr("placeholder", Drupal.t("Search"));
        });
    }
  };
})(jQuery, Drupal);
