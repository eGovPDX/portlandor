/**
 * @file
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.datetime_now = {
    attach: function (context, settings) {
      $(context).find('.datetime-now-button').each(function(index, element) {
        $(element).on('click', function() {
          const date = new Date();
          const offset = date.getTimezoneOffset();
          date.setMinutes(date.getMinutes() - offset);
          date.setSeconds(0);
          date.setMilliseconds(0);
          $(element).siblings().each(function(index, element) {
            $(element).find('input[type=date],input[type=time]').each(function(index, element) {
              element.valueAsDate = date;
            });
        })
        });
      });
    },
  };

})(jQuery, Drupal, drupalSettings);
