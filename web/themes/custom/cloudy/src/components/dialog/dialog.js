import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.dialog_handler = {
  attach(context, settings) {
    $(window).on('dialogcreate', function(e, dialog) {
      $('body')
        .find('.ui-dialog-titlebar-close')
        .once('fa-close-added')
        .each(function() {
          $(this).append('<i class="fa fa-window-close"></i>');
        });

        // Allow Linkit autocomplete selections to overflow outside of dialog window
        $('.ui-dialog .ui-dialog-content').has('.linkit-ui-autocomplete').css('overflow', 'inherit');
    });
  }
};
