Drupal.behaviors.liveRegion = {
  attach(context) {
    // What classes count as "visible messages"? Editors just assign these.
    const MESSAGE_CLASS = "form-alert"; // Change or make a list if needed

    // Minimal visibility check for incremental debugging.
    function isVisible(elem) {
      return window.getComputedStyle(elem).display !== "none";
    }

    // Main function: scan for visible alerts, copy the newest/loudest to aria-live
    function updateLiveRegion() {
      const messages = Array.from(document.querySelectorAll("." + MESSAGE_CLASS))
        .filter(isVisible)
        .map(e => e.textContent.trim())
        .filter(t => t.length);
      // If there are visible messages, announce the last-most (you can change to first-most, or severity-based selection)
      const liveRegion = document.getElementById("sr-live-message");
      if (liveRegion) {
        liveRegion.textContent = messages.length > 0 ? messages[messages.length - 1] : "";
      }
    }

    // Use once() to avoid attaching multiple times on AJAX updates
    once("liveRegion", "body", context).forEach(() => {
      // Set up a MutationObserver on the form (or document, if dynamic changes can happen anywhere)
      const targetNode = document;
      const observer = new MutationObserver(updateLiveRegion);

      observer.observe(targetNode, {
        childList: true,
        subtree: true,
        attributes: true,
        characterData: true
      });

      // Initial run
      updateLiveRegion();

      // Optional: if some messages are triggered by AJAX or other means, you may want to re-run on page interaction:
      document.addEventListener("change", updateLiveRegion, true); // For selects, radios, etc.
      document.addEventListener("input", updateLiveRegion, true); // For text fields
    });
  },
};