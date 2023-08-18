import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.drawer = {
  /**
   * @param {HTMLElement} context - HTML element to work within, always use `$('.my-class', context)`
   * @param {Object} settings - Drupal settings
   */
  attach(context, settings) {
    // Global Variables
    const openButton = $('.drawer__open');
    const closeButton = $('.drawer__close');

    // Add open to drawers
    $(once('drawerOpenHandlers', '.drawer__open', context)).each(function () {
        $(this).on('click', function(event) { 
        event.preventDefault();
        event.stopPropagation();

        const target = $(this).data('target');

        $(openButton).attr('aria-pressed', 'true');
        $(openButton).attr('aria-expanded', 'true');
        $(closeButton).attr('aria-pressed', 'false');
        $(target).addClass('is-active');
      });
    });

    // Add close to drawers
    $(once('drawerCloseHandlers', '.drawer__close', context)).each(function () {
        $(this).on('click', function(event) { 
        event.preventDefault();
        event.stopPropagation();

        const target = $(this).data('target');

        $(openButton).attr('aria-pressed', 'false');
        $(openButton).attr('aria-expanded', 'false');
        $(closeButton).attr('aria-pressed', 'true');
        $(target).removeClass('is-active');
      });
    });

    // Add close to overlay clicks
    $(once('drawerOverlayHandlers', '.drawer__overlay', context)).each(function () {
        $(this).on('click', function(event) { 
        event.preventDefault();
        event.stopPropagation();

        const target = $(this).data('bs-target');

        $(openButton).attr('aria-pressed', 'false');
        $(openButton).attr('aria-expanded', 'false');
        $(closeButton).attr('aria-pressed', 'true');
        $(target).removeClass('is-active');
      });
    });
  }
};
