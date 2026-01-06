import Collapse from "bootstrap/js/src/collapse";

/**
 * Add a data-cloudy-persist-collapse attribute to any collapsible element to persist its uncollapsed/collapsed state in localStorage.
 * The value of the attribute should be a globally unique identifier for the element.
 */
Drupal.behaviors.cloudyPersistCollapse = {
  attach(context) {
    once("cloudyPersistCollapse", "[data-cloudy-persist-collapse]", context).forEach((el) => {
      const key = `cloudyPersistCollapse.${el.getAttribute(
        "data-cloudy-persist-collapse",
      )}.collapsed`;
      const collapsedValue = localStorage.getItem(key);
      el.classList.add("no-transition");
      if (collapsedValue !== null) {
        const collapse = Collapse.getOrCreateInstance(el, { toggle: false });

        if (collapsedValue === "true") collapse.hide();
        else collapse.show();
      }
      el.classList.remove("no-transition");

      el.addEventListener("show.bs.collapse", () => localStorage.setItem(key, false));
      el.addEventListener("hide.bs.collapse", () => localStorage.setItem(key, true));
    });
  },
};
