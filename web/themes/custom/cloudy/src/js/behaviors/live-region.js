Drupal.behaviors.liveRegion = {
  attach(context) {
    const forms = [];
    if (context instanceof HTMLFormElement) {
      forms.push(context);
    }

    function isVisible(elem) {
      if (!elem) {
        return false;
      }

      let current = elem;
      while (current && current.nodeType === Node.ELEMENT_NODE) {
        const style = window.getComputedStyle(current);
        if (
          style.display === "none" ||
          style.visibility === "hidden" ||
          current.hasAttribute("hidden") ||
          current.getAttribute("aria-hidden") === "true"
        ) {
          return false;
        }

        current = current.parentElement;
      }

      return elem.getClientRects().length > 0;
    }

    function getAlertMessage(alert) {
      const heading = alert.querySelector("h2");
      const paragraphs = [...alert.querySelectorAll("p")];
      const parts = [heading, ...paragraphs]
        .filter(Boolean)
        .map((el) => (el.textContent || "").replace(/\s+/g, " ").trim())
        .filter(Boolean);

      if (parts.length > 0) {
        return parts.join(" ");
      }

      return (alert.textContent || "").replace(/\s+/g, " ").trim();
    }

    once("liveRegionFormObserver", "form", context).forEach((form) => {
      forms.push(form);
    });

    forms.forEach((form) => {
      const liveRegion = form.querySelector(".sr-live-message");
      if (!liveRegion) {
        return;
      }

      const visibleState = new WeakMap();

      function announceNewestVisibleAlert() {
        let latestMessage = "";

        [...form.querySelectorAll(".form-alert")].forEach((alert) => {
          const nowVisible = isVisible(alert);
          const wasVisible = visibleState.get(alert) === true;

          if (nowVisible && !wasVisible) {
            const message = getAlertMessage(alert);
            if (message) {
              latestMessage = message;
            }
          }

          visibleState.set(alert, nowVisible);
        });

        if (latestMessage) {
          liveRegion.textContent = latestMessage;
        }
      }

      let rafId = null;
      const observer = new MutationObserver(() => {
        if (rafId !== null) {
          cancelAnimationFrame(rafId);
        }

        rafId = requestAnimationFrame(() => {
          rafId = null;
          announceNewestVisibleAlert();
        });
      });

      observer.observe(form, {
        attributes: true,
        subtree: true,
        childList: true,
        attributeFilter: ["style", "class", "hidden", "aria-hidden"],
      });

      announceNewestVisibleAlert();
    });
  },
};
