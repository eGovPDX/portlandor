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
          const $text = Drupal.t("Search Portland.gov");
          $this.attr("placeholder") === $text
            ? $this.attr("placeholder", "")
            : $this.attr("placeholder", $text);
        });
    }
  };
})(jQuery, Drupal);
