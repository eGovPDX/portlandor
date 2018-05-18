/**
 * @file
 * Select-All Button functionality.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.views_bulk_operations = {
    attach: function (context, settings) {
      $('.vbo-view-form').once('vbo-init').each(Drupal.viewsBulkOperationsFrontUi);
    }
  };

  /**
   * Views Bulk Operation selection object.
   */
  Drupal.viewsBulkOperationsSelection = {
    view_id: '',
    display_id: '',
    list: {},
    $placeholder: null,

    /**
     * Bind event handlers to an element.
     *
     * @param {jQuery} element
     */
    bindEventHandlers: function ($element, index) {
      if ($element.length) {
        var selectionObject = this;
        $element.on('keypress', function (event) {
          // Emulate click action for enter key.
          if (event.which === 13) {
            event.preventDefault();
            event.stopPropagation();
            selectionObject.update(this.checked, index, $(this).val());
            $(this).trigger('click');
          }
          if (event.which === 32) {
            selectionObject.update(this.checked, index, $(this).val());
          }
        });
        $element.on('mousedown', function (event) {
          // Act only on left button click.
          if (event.which === 1) {
            selectionObject.update(this.checked, index, $(this).val());
          }
        });
      }
    },

    /**
     * Perform an AJAX request to update selection.
     *
     * @param {bool} state
     * @param {string} value
     */
    update: function (state, index, value) {
      if (value === undefined) {
        value = null;
      }
      if (this.view_id.length && this.display_id.length) {
        var list = {};
        if (value && value != 'on') {
          list[value] = this.list[index][value];
        }
        else {
          list = this.list[index];
        }
        var op = state ? 'remove' : 'add';

        var $placeholder = this.$placeholder;
        var target_uri = '/' + drupalSettings.path.pathPrefix + 'views-bulk-operations/ajax/' + this.view_id + '/' + this.display_id;
        $.ajax(target_uri, {
          method: 'POST',
          data: {
            list: list,
            op: op
          },
          success: function (data) {
            var count = parseInt($placeholder.text());
            count += data.change;
            $placeholder.text(count);
          }
        });
      }
    }
  }

  /**
   * Callback used in {@link Drupal.behaviors.views_bulk_operations}.
   */
  Drupal.viewsBulkOperationsFrontUi = function () {
    var $vboForm = $(this);
    var $viewsTables = $('.vbo-table', $vboForm);
    var $primarySelectAll = $('.vbo-select-all', $vboForm);
    var tableSelectAll = [];

    // When grouping is enabled, there can be multiple tables.
    if ($viewsTables.length) {
      $viewsTables.each(function (index) {
        tableSelectAll[index] = $(this).find('.select-all input').first();
      });
      var $tableSelectAll = $(tableSelectAll);
    }

    // Add AJAX functionality to table checkboxes.
    var $multiSelectElement = $vboForm.find('.vbo-multipage-selector').first();
    if ($multiSelectElement.length) {

      Drupal.viewsBulkOperationsSelection.$placeholder = $multiSelectElement.find('.placeholder').first();
      Drupal.viewsBulkOperationsSelection.view_id = $multiSelectElement.attr('data-view-id');
      Drupal.viewsBulkOperationsSelection.display_id = $multiSelectElement.attr('data-display-id');

      // Get the list of all checkbox values and add AJAX callback.
      Drupal.viewsBulkOperationsSelection.list = [];

      var $contentWrappers;
      if ($viewsTables.length) {
        $contentWrappers = $viewsTables;
      }
      else {
        $contentWrappers = $([$vboForm]);
      }

      $contentWrappers.each(function (index) {
        var $contentWrapper = $(this);
        Drupal.viewsBulkOperationsSelection.list[index] = {};

        $contentWrapper.find('.views-field-views-bulk-operations-bulk-form input[type="checkbox"]').each(function () {
          var value = $(this).val();
          if (value != 'on') {
            Drupal.viewsBulkOperationsSelection.list[index][value] = $(this).parent().find('label').first().text();
            Drupal.viewsBulkOperationsSelection.bindEventHandlers($(this), index);
          }
        });

        // Bind event handlers to select all checkbox.
        if ($viewsTables.length && tableSelectAll.length) {
          Drupal.viewsBulkOperationsSelection.bindEventHandlers(tableSelectAll[index], index);
        }
      });
    }

    // Initialize all selector if the primary select all and
    // view table elements exist.
    if ($primarySelectAll.length && $viewsTables.length) {
      var strings = {
        selectAll: $('label', $primarySelectAll.parent()).html(),
        selectRegular: Drupal.t('Select only items on this page')
      };

      $primarySelectAll.parent().hide();

      if ($viewsTables.length == 1) {
        var colspan = $('thead th', $viewsTables.first()).length;
        var $allSelector = $('<tr class="views-table-row-vbo-select-all even" style="display: none"><td colspan="' + colspan + '"><div><input type="submit" class="form-submit" value="' + strings.selectAll + '"></div></td></tr>');
        $('tbody', $viewsTables.first()).prepend($allSelector);
      }
      else {
        var $allSelector = $('<div class="views-table-row-vbo-select-all" style="display: none"><div><input type="submit" class="form-submit" value="' + strings.selectAll + '"></div></div>');
        $($viewsTables.first()).before($allSelector);
      }

      if ($primarySelectAll.is(':checked')) {
        $('input', $allSelector).val(strings.selectRegular);
        $allSelector.show();
      }
      else {
        var show_all_selector = true;
        $tableSelectAll.each(function () {
          if (!$(this).is(':checked')) {
            show_all_selector = false;
          }
        });
        if (show_all_selector) {
          $allSelector.show();
        }
      }

      $('input', $allSelector).on('click', function (event) {
        event.preventDefault();
        if ($primarySelectAll.is(':checked')) {
          $multiSelectElement.show('fast');
          $primarySelectAll.prop('checked', false);
          $allSelector.removeClass('all-selected');
          $(this).val(strings.selectAll);
        }
        else {
          $multiSelectElement.hide('fast');
          $primarySelectAll.prop('checked', true);
          $allSelector.addClass('all-selected');
          $(this).val(strings.selectRegular);
        }
      });

      $(tableSelectAll).each(function () {
        $(this).on('change', function (event) {
          var show_all_selector = true;
          $tableSelectAll.each(function () {
            if (!$(this).is(':checked')) {
              show_all_selector = false;
            }
          });
          if (show_all_selector) {
            $allSelector.show();
          }
          else {
            $allSelector.hide();
            if ($primarySelectAll.is(':checked')) {
              $('input', $allSelector).trigger('click');
            }
          }
        });
      });
    }
    else {
      $primarySelectAll.first().on('change', function (event) {
        if (this.checked) {
          $multiSelectElement.hide('fast');
        }
        else {
          $multiSelectElement.show('fast');
        }
      });
    }
  };

})(jQuery, Drupal);
