(function ($, Drupal, drupalSettings, L) {
  /**
   * Represents a Location Picker Controller.
   * This controller manages the interaction between the model and view
   * for selecting and displaying locations.
   *
   * @class
   */
  class LocationPickerController {
    /**
     * Creates a new LocationPickerController instance.
     * @constructor
     */
    constructor() {
      //this.formSettings = formSettings;
      var test = drupalSettings;
      this.model = new LocationPickerModel(this);
      this.view = new LocationPickerView(this);

      // this.view.renderTodos(this.model.todos);

      // ----- attach event handlers ----- //

      // disable form submit when pressing enter on address field and click Verify button instead
      $('#location_address').on('keydown', function (e) {
        if (e.keyCode == 13) {
          e.preventDefault();
          if (!verifyHidden) {
            $('#location_verify').click();
          }
          return false;
        }
      });
    }

    // ----- utilities ----- //
    logError(text) {
      console.log(text);
    }

    addTodoItem(text) {
      this.model.addTodo(text);
      this.view.renderTodos(this.model.todos);
    }

    toggleTodoItem(index) {
      this.model.toggleTodo(index);
      this.view.renderTodos(this.model.todos);
    }

    getTestMessage(appendedMessage) {
      return MESSAGE_CONSTANTS.OPEN_ISSUE_MESSAGE + " " + appendedMessage;
    }
  }

  // Export the controller class
  window.LocationPickerController = LocationPickerController;
})(jQuery, Drupal, drupalSettings, L);
