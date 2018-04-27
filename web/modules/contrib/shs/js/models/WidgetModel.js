/**
 * @file
 * A Backbone Model for a single widget in SHS.
 */

(function ($, Backbone, Drupal) {

  'use strict';


  /**
   * Backbone model for a single widget in SHS.
   *
   * @constructor
   *
   * @augments Backbone.Model
   */
  Drupal.shs.WidgetModel = Backbone.Model.extend(/** @lends Drupal.shs.WidgetModel# */{

    /**
     * @type {object}
     *
     * @prop {object} items
     */
    defaults: /** @lends Drupal.shs.WidgetModel# */{

      /**
       * Default value of widget.
       *
       * @type {string}
       */
      defaultValue: '_none',

      /**
       * Position of widget in app.
       *
       * @type {integer}
       */
      level: 0,

      /**
       * Collection of items in widget (options).
       *
       * @type {Drupal.shs.WidgetItemCollection}
       */
      itemCollection: null,

      /**
       * Indicator whether data for this model has been loaded already.
       *
       * @type {boolean}
       */
      dataLoaded: false
    },

    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      // Set internal id.
      this.set('id', options.id);
    }
  });

}(jQuery, Backbone, Drupal));
