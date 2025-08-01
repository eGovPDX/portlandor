/**
 * @file
 * Add a link to expand or collapse all accordion sections.
 */

Drupal.behaviors.cloudyExpandAllAccordion = {
  STR_EXPAND_ALL: Drupal.t("Expand all"),
  STR_COLLAPSE_ALL: Drupal.t("Collapse all"),

  /**
   * Toggles a single accordion panel to provided open state.
   */
  toggleAccordionPanel(panelEl, open) {
    document
      .querySelector(`[aria-controls="${panelEl.id}"]`)
      .setAttribute("aria-expanded", open ? "true" : "false");

    if (open) {
      panelEl.removeAttribute("hidden");
    } else {
      panelEl.setAttribute("hidden", "");
    }
  },

  /**
   * Expand and scrolls to the accordion panel containing a child with a matching ID.
   */
  scrollToPanelContainingId(id) {
    const panelEl = document.getElementById(id)?.closest(".aria-accordion__panel");
    if (!panelEl) return;

    this.toggleAccordionPanel(panelEl, true);
    setTimeout(
      () => document.querySelector(`[aria-controls="${panelEl.id}"]`).scrollIntoView({}),
      0,
    );
  },

  attach(context, drupalSettings) {
    const minRows = drupalSettings?.portland?.cloudyExpandAllAccordion?.minRows ?? 0;
    window.addEventListener("DOMContentLoaded", () => {
      const hashId = location.hash.slice(1);
      if (hashId) this.scrollToPanelContainingId(hashId);

      once("cloudyExpandAllAccordion", "div.aria-accordion", context).forEach((accordion) => {
        const accordionPanelIds = Array.from(
          accordion.querySelectorAll("div.aria-accordion__panel"),
        ).map((el) => el.id);
        if (accordionPanelIds.length < minRows) {
          // Collapse first row if there aren't enough rows to enable this feature
          accordion
            .querySelector(".aria-accordion__heading > button")
            .setAttribute("aria-expanded", "false");
          accordion.querySelector(".aria-accordion__panel").setAttribute("hidden", "");
          return;
        }

        accordion.insertAdjacentHTML(
          "afterbegin",
          `
            <button type="button" class="toggle-accordion btn btn-link d-block ms-auto mb-1 p-0" aria-expanded="false" aria-controls="${accordionPanelIds.join(
              " ",
            )}">${this.STR_EXPAND_ALL}</a>
          `,
        );
        accordion.addEventListener("click", (e) => {
          const toggleControl = e.target;
          if (!toggleControl.classList.contains("toggle-accordion")) return;

          const isExpanding = toggleControl.getAttribute("aria-expanded") === "false";
          accordion
            .querySelectorAll(".aria-accordion__panel")
            .forEach((el) => this.toggleAccordionPanel(el, isExpanding));
          toggleControl.setAttribute("aria-expanded", isExpanding ? "true" : "false");
          toggleControl.textContent = isExpanding ? this.STR_COLLAPSE_ALL : this.STR_EXPAND_ALL;
        });
      });
    });

    window.addEventListener("click", (e) => {
      if (e.target.tagName !== "A" || !e.target.hash) return;

      this.scrollToPanelContainingId(e.target.hash.slice(1));
    });
  },
};
