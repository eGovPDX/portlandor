import Collapse from "bootstrap/js/src/collapse";

/**
 * Add a data-cloudy-esc-collapse attribute to any collapsible element to close it when the escape key is pressed if it has focus.
 */
Drupal.behaviors.cloudyEscCollapse = {
  attach(context) {
    const elements = once("cloudyEscCollapse", "[data-cloudy-esc-collapse]", context);
    document.body.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        elements.forEach((el) => {
          if (el.matches(":focus-within")) Collapse.getInstance(el).hide();
        });
      }
    });
  },
};
