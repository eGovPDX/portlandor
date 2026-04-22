Drupal.behaviors.liveRegion = {
  attach(context) {
    const liveRegion = document.getElementById("sr-live-message");
    if (!liveRegion) {
      return;
    }

    once("liveRegionVisibleLog", ".form-alert", context).forEach((alert) => {
      const watchRoot = alert.closest("form") || document.body;
      let wasVisible = isVisible(alert);

      function isVisible(elem) {
        const style = window.getComputedStyle(elem);
        return (
          style.display !== "none" &&
          style.visibility !== "hidden" &&
          elem.getClientRects().length > 0
        );
      }

      function announceIfVisible() {
        const nowVisible = isVisible(alert);

        if (nowVisible && !wasVisible) {
          const message = (alert.textContent || "").replace(/\s+/g, " ").trim();
          if (message) {
            liveRegion.textContent = `Alert: ${message}`;
          }
        }

        wasVisible = nowVisible;
      }

      const observer = new MutationObserver(announceIfVisible);

      observer.observe(watchRoot, {
        attributes: true,
        subtree: true,
        childList: true,
        attributeFilter: ["style", "class", "hidden", "aria-hidden"],
      });

      announceIfVisible();
    });
  },
};
