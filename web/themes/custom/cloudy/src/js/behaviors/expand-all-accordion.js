/**
 * @file
 * Add a link to expand or collapse all accordion sections.
 */

Drupal.behaviors.cloudyExpandAllAccordion = {
  STR_EXPAND_ALL: Drupal.t("Expand all"),
  STR_COLLAPSE_ALL: Drupal.t("Collapse all"),

  attach(context) {
    window.addEventListener("DOMContentLoaded", () => {
      once("cloudyExpandAllAccordion", "div.aria-accordion", context).forEach((accordion) => {
        const accordionPanelIds = Array.from(
          accordion.querySelectorAll("div.aria-accordion__panel")
        ).map((el) => el.getAttribute("id"));
        accordion.insertAdjacentHTML(
          "afterbegin",
          `
            <button type="button" class="toggle-accordion btn btn-link d-block ms-auto mb-1 p-0" aria-expanded="false" aria-controls="${accordionPanelIds.join(
              " "
            )}">${this.STR_EXPAND_ALL}</a>
          `
        );
        accordion.addEventListener("click", (e) => {
          const toggleControl = e.target;
          if (!toggleControl.classList.contains("toggle-accordion")) return;

          const isExpanding = toggleControl.getAttribute("aria-expanded") === "false";
          accordion
            .querySelectorAll(".aria-accordion__heading > button")
            .forEach((el) => el.setAttribute("aria-expanded", isExpanding ? "true" : "false"));
          accordion
            .querySelectorAll(".aria-accordion__panel")
            .forEach((el) =>
              isExpanding ? el.removeAttribute("hidden") : el.setAttribute("hidden", "")
            );
          toggleControl.setAttribute("aria-expanded", isExpanding ? "true" : "false");
          toggleControl.textContent = isExpanding ? this.STR_COLLAPSE_ALL : this.STR_EXPAND_ALL;
        });
      });
    });
  },
};
