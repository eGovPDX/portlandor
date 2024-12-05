import throttle from "lodash.throttle";

Drupal.behaviors.cloudyBackToTop = {
  attach(context) {
    once("cloudyBackToTop", ".cloudy-back-to-top", context).forEach((btnEl) => {
      const docEl = document.documentElement;
      let minHeightToShow = window.innerHeight * 0.75;

      window.addEventListener("resize", () => {
        minHeightToShow = window.innerHeight * 0.75;
      });

      window.addEventListener(
        "scroll",
        throttle(() => {
          const scrollTop = docEl.scrollTop;
          if (scrollTop >= minHeightToShow) {
            btnEl.classList.remove("d-none");
          } else if (scrollTop < minHeightToShow) {
            btnEl.classList.add("d-none");
          }
        }, 300),
      );

      btnEl.addEventListener("click", (e) => {
        e.preventDefault();
        // scroll to x = x, y = 0
        docEl.scrollTo(docEl.scrollLeft, 0);
      });
    });
  },
};
