/**
 * @file
 * Search API Page placeholder functionality.
 *
 * hides search input placeholder text on focus
 */
(function($, Drupal) {
  Drupal.behaviors.cloudyHideSearchPlaceholder = {
    attach: function(context, settings) {
      $(context)
        .find("#edit-keys.ui-autocomplete-input[data-search-api-autocomplete-search]")
        .once("search-clear")
        .on("focusin focusout", function() {
          const $this = $(this);
          if ($this.attr("placeholder").length) {
            $this.data("placeholder", $this.attr("placeholder"));
          }

          $this.attr("placeholder") === $this.data("placeholder")
            ? $this.attr("placeholder", "")
            : $this.attr("placeholder", $this.data("placeholder"));
        });
    }
  };
})(jQuery, Drupal);
