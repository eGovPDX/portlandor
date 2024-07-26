class LocationWidgetController {
    constructor(element, model, view) {
      this.element = element;
      this.model = model;
      this.view = view;

      console.log('LocationWidgetController is plugged in');
    }
  
    init() {
      // initialize the location widget
      this.view.initMap();
    }
  
    updateView(locations) {
      // update view
    }
  }
  