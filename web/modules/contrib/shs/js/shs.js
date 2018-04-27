/**
 * @file
 * Defines the behavior of the Simple hierarchical select module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * @todo: add description
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the SHS rendering functionality to matching elements.
   */
  Drupal.behaviors.shs = {
    attach: function (context) {
      var settingsDefault = {
        display: {
          animationSpeed: 400,
          labelsOnEveryLevel: false
        },
        labels: []
      };

      $(context).find('select.shs-enabled:not([disabled])').not('.shs-processed').addClass('shs-processed').each(function () {
        var field = this;
        var field_name = $(field).attr('data-shs-selector');
        if (!drupalSettings.hasOwnProperty('shs') || !drupalSettings.shs.hasOwnProperty(field_name)) {
          return;
        }

        var config = $.extend({}, settingsDefault, drupalSettings.shs[field_name], {
          fieldName: field_name
        });
        if (drupalSettings.shs[field_name].hasOwnProperty('display')) {
          config.display = $.extend(true, {}, settingsDefault.display, drupalSettings.shs[field_name].display);
        }
        // Initialize model and view classes for the field.
        Drupal.behaviors.shs.initClasses(config.fieldName, config.classes);

        if (!Drupal.shs.classes[config.fieldName].hasOwnProperty('models')) {
          return;
        }

        // Initialize application model.
        var app_model = new Drupal.shs.classes[config.fieldName].models.app({
          config: config
        });

        // Initialize application view.
        var app_view = new Drupal.shs.classes[config.fieldName].views.app({
          el: field,
          model: app_model
        });
        app_view.render();

        // Broadcast model changes to other modules.
//        widget_model.on('change:items', function (model) {
//          $(document).trigger('shsWidgetItemsChange');
//        });
      });
    },
    /**
     * Initialize model and widget classes.
     *
     * Using the class storage instead of directly calling (i.e.)
     * <code>new Drupal.shs.WidgetView()</code> allows other modules to
     * override every part of the widget generation.
     *
     * @see hook_shs_class_definitions_alter()
     * @see hook_shs_FIELDNAME_class_definitions_alter()
     *
     * @param {string} fieldName
     *   Name of field to initialize the classes for.
     * @param {object} definitions
     *   List of class names.
     */
    initClasses: function (fieldName, definitions) {
      Drupal.shs.classes[fieldName] = Drupal.shs.classes[fieldName] || {
        models: {},
        views: {}
      };
      $.each(definitions.models, function (modelKey, modelClass) {
        Drupal.shs.classes[fieldName].models[modelKey] = Drupal.shs.getClass(modelClass);
      });
      $.each(definitions.views, function (viewKey, viewClass) {
        Drupal.shs.classes[fieldName].views[viewKey] = Drupal.shs.getClass(viewClass);
      });
    }
  };

  /**
   * SHS methods of Backbone objects.
   *
   * @namespace
   */
  Drupal.shs = {

    /**
     * A hash of model and view classes for each field.
     *
     * @type {object}
     */
    classes: {},

    /**
     * Get view/model class from name. Allows overriding every class within shs.
     *
     * @param {string} classname
     *   Name of class to load.
     * @returns {object}
     *   Instantiable class.
     */
    getClass: function (classname) {
      var parts = classname.split('.');

      var fn = (window || this);
      for (var i = 0, len = parts.length; i < len; i++) {
        fn = fn[parts[i]];
      }

      if (typeof fn !== 'function') {
        throw new Error('Class/function not found: [' + classname + ']');
      }

      return fn;
    }

  };

}(jQuery, Drupal, drupalSettings));
