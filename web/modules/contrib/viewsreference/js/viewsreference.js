/**
 * @file
 */

(function ($) {

  'use strict';

  /**
   * Handles an autocompleteselect event.
   *
   * Override the autocomplete method to add a custom event.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {bool}
   *   Returns false to indicate the event status.
   */
  Drupal.autocomplete.options.select = function selectHandler(event, ui) {
    var terms = Drupal.autocomplete.splitValues(event.target.value);
    // Remove the current input.
    terms.pop();
    // Add the selected item.
    if (ui.item.value.search(',') > 0) {
      terms.push('"' + ui.item.value + '"');
    }
    else {
      terms.push(ui.item.value);
    }
    event.target.value = terms.join(', ');
    // Fire custom event that other controllers can listen to.
    jQuery(event.target).trigger('viewsreference-select');
    // Return false to tell jQuery UI that we've filled in the value already.
    return false;
  };

  Drupal.behaviors.displayMessage = {
    attach: function (context, settings) {
      $(document).ajaxComplete(function (event, request, settings) {
        $('.field--type-viewsreference .viewsreference-display-id').each(function () {
          if (!$(this).find('option').length) {
            var html = '<p class="viewsreference-display-error form-notice color-warning">There is no Display available.  Please select another view or change the field settings.</p>';
            $(this).parent().remove('.viewsreference-display-error');
            $('.viewsreference-display-error').remove();
            $(this).parent().append(html);
          }
        });
      });
    }
  };

})(jQuery, drupalSettings);
