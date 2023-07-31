/**
 * @file
 * Search API Page placeholder functionality.
 *
 * hides search input placeholder text on focus
 */
(function($, Drupal) {
  Drupal.behaviors.cloudyHideSearchPlaceholder = {
    attach: function(context, settings) {
      $(once('search-clear', '#edit-keys.ui-autocomplete-input[data-search-api-autocomplete-search]', context)).each(function () {
        $(this).on('focusin focusout', function() {    
          const $this = $(this);
          if ($this.attr("placeholder").length) {
            $this.data("placeholder", $this.attr("placeholder"));
          }

          $this.attr("placeholder") === $this.data("placeholder")
            ? $this.attr("placeholder", "")
            : $this.attr("placeholder", $this.data("placeholder"));
        });
      });
    }
  };
})(jQuery, Drupal);
