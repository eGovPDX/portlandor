/**
 * @file
 * A Backbone View that controls a Simple hierarchical select widget container.
 *
 * @see Drupal.shs.ContainerModel
 */

(function ($, _, Backbone, Drupal) {

  'use strict';

  Drupal.shs.ContainerView = Backbone.View.extend(/** @lends Drupal.shs.ContainerView# */{
    /**
     * The main application.
     *
     * @type {Drupal.shs.AppView}
     */
    app: null,
    /**
     * Default tagname of this view.
     *
     * @type {string}
     */
    tagName: 'div',
    /**
     * @constructs
     *
     * @augments Backbone.View
     *
     * @param {object} options
     *   An object with the following keys:
     * @param {Drupal.shs.AppView} options.app
     *   The application state view.
     */
    initialize: function (options) {
      this.app = options.app;

      // Set default value.
      var defaultValue = this.app.getConfig('defaultValue');
      if (null !== defaultValue && defaultValue.hasOwnProperty(this.model.get('delta'))) {
        this.model.set('value', this.app.getConfig('defaultValue')[this.model.get('delta')]);
      }

      this.collection = new Drupal.shs.WidgetCollection({
        url: this.app.getConfig('baseUrl') + '/' + this.app.getConfig('fieldName') + '/' + this.app.getConfig('bundle')
      });
      this.collection.reset();

      this.listenTo(this.collection, 'initialize:shs-container', this.renderWidgets);
      this.listenTo(this.collection, 'update:selection', this.selectionUpdate);
      this.listenTo(this.collection, 'update:value', this.broadcastUpdate);
    },
    /**
     * Main render function for a widget container.
     *
     * @return {Drupal.shs.ContainerView}
     *   Returns ContainerView for chaining.
     */
    render: function () {
      var container = this;

      // Set default classes and clear content.
      container.$el.addClass('shs-field-container')
              .attr('data-shs-delta', container.model.get('delta'))
              .html('');

      $.each(container.model.get('parents'), function (index, item) {
        // Add WidgetModel for each parent.
        container.collection.add(new Drupal.shs.classes[container.app.getConfig('fieldName')].models.widget({
          id: item.parent,
          defaultValue: item.defaultValue,
          level: index
        }));
      });

      // Trigger events.
      container.collection.trigger('initialize:shs-container');

      return container;
    },
    /**
     * Renders the select widgets of Simple hierarchical select.
     *
     * @return {Drupal.shs.ContainerView}
     *   Returns ContainerView for chaining.
     */
    renderWidgets: function () {
      var container = this;
      container.$el.html('');
      container.collection.each(function (widgetModel) {
        // Create container for widget.
        container.$el.append($('<div>').addClass('shs-widget-container').attr('data-shs-level', widgetModel.get('level')));
        // Create widget.
        new Drupal.shs.classes[container.app.getConfig('fieldName')].views.widget({
          container: container,
          model: widgetModel
        });
      });

      return container;
    },
    /**
     * Rebuild widgets based on changed selection.
     *
     * @param {Drupal.shs.WidgetModel} widgetModel
     *   The changed model.
     * @param {string} value
     *   New value of WidgetView
     * @param {Drupal.shs.WidgetView} widgetView
     *   View displaying the model.
     */
    selectionUpdate: function (widgetModel, value, widgetView) {
      var container = this;
      // Find all WidgetModels with a higher level than the changed model.
      var models = _.filter(this.collection.models, function (model) {
        return model.get('level') > widgetModel.get('level');
      });
      // Remove the found models from the collection.
      container.collection.remove(models);

      var anyValue = container.app.getSetting('anyValue');
      if (value !== anyValue) {
        // Add new model with current selection.
        container.collection.add(new Drupal.shs.classes[container.app.getConfig('fieldName')].models.widget({
          id: value,
          level: widgetModel.get('level') + 1
        }));
      }
      if (value === anyValue && widgetModel.get('level') > 0) {
        // Use value of parent widget (which is the id of the model ;)).
        value = widgetModel.get('id');
      }

      // Update parents.
      var parents = [];
      var previousParent = 0;
      container.collection.each(function (widgetModel) {
        parents.push({
          defaultValue: widgetModel.get('defaultValue'),
          parent: previousParent
        });
        previousParent = widgetModel.get('defaultValue');
      });
      container.model.set('parents', parents);
      container.model.set('value', value);
      // Trigger value update.
      container.collection.trigger('update:value', widgetModel, value);

      // Trigger rerender of widgets.
      container.collection.trigger('initialize:shs-container');
    },
    /**
     * Broadcast value update to application.
     *
     * @param {Drupal.shs.WidgetModel} widgetModel
     *   The changed model.
     * @param {string} value
     *   New value of element.
     */
    broadcastUpdate: function (widgetModel, value) {
      var app = this.app;
      if (value === app.getSetting('anyValue') && widgetModel.get('level') > 0) {
        // Use value of parent widget (which is the id of the model ;)).
        value = widgetModel.get('id');
      }
      // Send updated value to application.
      app.updateElementValue(value, this, widgetModel);
    }
  });

  /**
   * @constructor
   *
   * @augments Backbone.Collection
   */
  Drupal.shs.WidgetCollection = Backbone.Collection.extend(/** @lends Drupal.shs.WidgetCollection */{
    /**
     * @type {Drupal.shs.WidgetModel}
     */
    model: Drupal.shs.WidgetModel,
    /**
     * {@inheritdoc}
     */
    initialize: function (options) {
      this.url = options.url;
    }
  });

}(jQuery, _, Backbone, Drupal));
