
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
    this.model = new LocationPickerModel();
    this.view = new LocationPickerView(this);

    this.view.renderTodos(this.model.todos);
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

