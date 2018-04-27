/**
 * @file
 * A Backbone view for a shs widget optionally rendered as chosen element.
 */

(function ($, Drupal, drupalSettings, Backbone) {

  'use strict';

  Drupal.shs_chosen = Drupal.shs_chosen || {};

  Drupal.shs_chosen.ChosenWidgetView = Drupal.shs.WidgetView.extend(/** @lends Drupal.shs_chosen.ChosenWidgetView# */{
    /**
     * @inheritdoc
     */
    render: function () {
      // Call parent render function.
      var widget = Drupal.shs.WidgetView.prototype.render.apply(this);
      if (widget.container.app.getConfig('display.chosen')) {
        widget.$el.addClass('chosen-enable');
        var settings = {
          chosen: {}
        };
        $.extend(true, settings.chosen, widget.container.app.getConfig('display.chosen'));
        // Attach chosen behavior.
        Drupal.behaviors.chosen.attach(widget.container.$el, settings);
      }

      // Return self for chaining.
      return widget;
    }
  });

}(jQuery, Drupal, drupalSettings, Backbone));
