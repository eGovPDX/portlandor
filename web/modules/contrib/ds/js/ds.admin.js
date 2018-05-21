/**
 * @file
 * Javascript functionality for Display Suite's administration UI.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.DSSummaries = {
    attach: function (context) {
      var $context = $(context);
      $context.find('#edit-fs1').drupalSetSummary(function (context) {
        var fieldtemplates = $('#edit-fs1-field-template', context);

        if (fieldtemplates.is(':checked')) {
          var fieldtemplate = $('#edit-fs1-ft-default option:selected').text();
          return Drupal.t('Enabled') + ': ' + Drupal.t(fieldtemplate);
        }

        return Drupal.t('Disabled');
      });
    }
  };

})(jQuery, Drupal);
