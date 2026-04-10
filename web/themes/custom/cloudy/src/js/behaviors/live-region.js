Drupal.behaviors.liveRegion = {
  attach(context) {
    once("liveRegionVisibleLog", "body", context).forEach(() => {
      const liveRegion = document.getElementById("sr-live-message");

      if (!liveRegion) {
        return;
      }

      const alerts = document.querySelectorAll(".form-alert");

      alerts.forEach((alert) => {
        const wrapper = alert.closest(".js-webform-states-hidden.js-form-wrapper");
        if (!wrapper) {
          return;
        }

        let wasVisible = isVisible(wrapper);

        function isVisible(elem) {
          const style = window.getComputedStyle(elem);
          return style.display !== "none" && elem.getClientRects().length > 0;
        }

        function checkVisibility() {
          const nowVisible = isVisible(wrapper);

          if (nowVisible && !wasVisible) {
            liveRegion.textContent = "Alert: " + (alert.textContent || "New message");
            console.log("Alert visible - live region updated");
          }

          wasVisible = nowVisible;
        }

        const observer = new MutationObserver(checkVisibility);

        observer.observe(wrapper, {
          attributes: true,
          attributeFilter: ["style", "class"],
        });

        checkVisibility();
      });
    });
  },
};
