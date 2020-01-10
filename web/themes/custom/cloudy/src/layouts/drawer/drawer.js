import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.drawer = {
  /**
  * @param {HTMLElement} context - HTML element to work within, always use `$('.my-class', context)`
  * @param {Object} settings - Drupal settings
  */
  attach(context, settings) {
    // Good habits: always scope selectors to `context`, and ensure each is fired only `.once()`.
    // Due to AJAX and Drupal behaviors JS can fire many times: injected content, new views pages loading etc
    $('.drawer', context).once('drawer-mount').each(function() {
      const $item = $(this);
    });
  }
};
