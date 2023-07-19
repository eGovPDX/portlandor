import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.dialog_handler = {
  attach(context, settings) {
    $(window).on('dialogcreate', function(e, dialog) {
      $(once('fa-close-added', 'body .ui-dialog-titlebar-close'))
        .each(function() {
          $(this).append('<i class="fa fa-window-close"></i>');
        });

        // Allow Linkit autocomplete selections to overflow outside of dialog window
        $('.ui-dialog .ui-dialog-content').has('.linkit-ui-autocomplete').css('overflow', 'inherit');
    });
  }
};
