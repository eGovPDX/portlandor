import Collapse from "bootstrap/js/src/collapse";

/**
 * Add a data-cloudy-esc-collapse attribute to any collapsible element to close it when the escape key is pressed if it has focus.
 * ASSUMPTION: Should only be used with collapse elements that only have one trigger element,
 *             since the first trigger element will be used to return focus.
 */
Drupal.behaviors.cloudyEscCollapse = {
  attach(context) {
    const elements = once("cloudyEscCollapse", "[data-cloudy-esc-collapse]", context);
    document.body.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        elements.forEach((el) => {
          const collapse = Collapse.getInstance(el);
          if (
            el.matches(":focus-within") ||
            collapse._triggerArray?.[0]?.matches(":focus-within")
          ) {
            collapse._triggerArray?.[0]?.focus();

            collapse.hide();
          }
        });
      }
    });
  },
};
