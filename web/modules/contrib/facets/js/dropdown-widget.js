/**
 * @file
 * Transforms links into a dropdown list.
 */

(function ($) {

  'use strict';

  Drupal.facets = Drupal.facets || {};
  Drupal.behaviors.facetsDropdownWidget = {
    attach: function (context, settings) {
      Drupal.facets.makeDropdown(context, settings);
    }
  };

  /**
   * Turns all facet links into a dropdown with options for every link.
   *
   * @param {object} context
   *   Context.
   * @param {object} settings
   *   Settings.
   */
  Drupal.facets.makeDropdown = function (context, settings) {
    // Find all dropdown facet links and turn them into an option.
    $('.js-facets-dropdown-links').once('facets-dropdown-transform').each(function () {
      var $ul = $(this);
      var $links = $ul.find('.facet-item a');
      var $dropdown = $('<select />');
      // Preserve all attributes of the list.
      $ul.each(function() {
        $.each(this.attributes,function(idx, elem) {
            $dropdown.attr(elem.name, elem.value);
        });
      });
      // Remove the class which we are using for .once().
      $dropdown.removeClass('js-facets-dropdown-links');

      $dropdown.addClass('facets-dropdown');

      var id = $(this).data('drupal-facet-id');
      var default_option_label = settings.facets.dropdown_widget[id]['facet-default-option-label'];
      // Add empty text option first.
      var $default_option = $('<option />')
        .attr('value', '')
        .text(default_option_label);
      $dropdown.append($default_option);

      var has_active = false;
      $links.each(function () {
        var $link = $(this);
        var active = $link.hasClass('is-active');
        var $option = $('<option />')
          .attr('value', $link.attr('href'))
          .data($link.data());
        if (active) {
          has_active = true;
          // Set empty text value to this link to unselect facet.
          $default_option.attr('value', $link.attr('href'));

          $option.attr('selected', 'selected');
          $link.find('.js-facet-deactivate').remove();
        }
        $option.html($link.text());
        $dropdown.append($option);
      });

      // Go to the selected option when it's clicked.
      $dropdown.on('change.facets', function () {
        window.location.href = $(this).val();
      });

      // Append empty text option.
      if (!has_active) {
        $default_option.attr('selected', 'selected');
      }

      // Replace links with dropdown.
      $ul.after($dropdown).remove();
      Drupal.attachBehaviors($dropdown.parent()[0], Drupal.settings);
    });
  };

})(jQuery);
