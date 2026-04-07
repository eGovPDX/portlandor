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
          const ed11yEl = document.querySelector('ed11y-element-panel');
          if (scrollTop >= minHeightToShow) {
            btnEl.classList.remove("d-none");
            ed11yEl?.classList.add("ed11y-element-panel__above-back-to-top");
          } else if (scrollTop < minHeightToShow) {
            btnEl.classList.add("d-none");
            ed11yEl?.classList.remove("ed11y-element-panel__above-back-to-top");
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
