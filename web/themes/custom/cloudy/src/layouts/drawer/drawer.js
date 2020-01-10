import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.drawer = {
  /**
   * @param {HTMLElement} context - HTML element to work within, always use `$('.my-class', context)`
   * @param {Object} settings - Drupal settings
   */
  attach(context, settings) {
    // Add open to drawers
    $(document, context).once('drawerOpenHandlers').on('click', '.drawer__trigger', function(event) {
      event.preventDefault();
      event.stopPropagation();

      // Defining Variables
      const target = $(this).data('target');

      $(target).addClass('is-active');
    });

    // Add close to drawers
    $(document, context).once('drawerCloseHandlers').on('click', '.drawer__close', function(event) {
      event.preventDefault();
      event.stopPropagation();

      // Defining Variables
      const target = $(this).data('target');

      $(target).removeClass('is-active');
    });

    // Add close to overlay clicks
    $(document, context).once('drawerOverlayHandlers').on('click', '.drawer__overlay', function(event) {
      event.preventDefault();
      event.stopPropagation();

      $('.drawer').removeClass('is-active');
    });
  }
};
