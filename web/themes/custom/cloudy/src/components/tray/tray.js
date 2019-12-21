import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.cloudyTrayHandler = {
  attach: function (context) {
    // Global call only triggered once
    $(document, context).once('cloudyTrayHandler').on('click', '[data-toggle="class"]', function(event) {
      event.preventDefault();
      event.stopPropagation();

      // Defining Variables
      const target = $(this).data('target');
      const classes = $(this).data('classes');
      const tray = $('.cloudy-tray');

      // If user clicks outside the tray, hide it!
      $(document).on('click', function(event) {
        const isOutside = !tray.is(event.target) && tray.has(event.target).length === 0 && $(event.target).closest(tray).length == 0;

        if(isOutside) {
          $(target).removeClass('is-active');
        }
      })

      // Toggle active class on body, tray, and overlay
      $(target).toggleClass(classes);
    });
  }
};
