import LocationPickerModel from '../models/locationPickerModel.js';
import LocationPickerView from '../views/locationPickerView.js';

class LocationPickerController {
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
}

Drupal.behaviors.locationPicker = {
  attach: (context, settings) => {
    if (context === document) {
      // Create an instance of the controller and interact with it as needed.
      const controller = new LocationPickerController();
      // Attach event listeners and interact with the controller.
    }
  }
};

export default LocationPickerController;
