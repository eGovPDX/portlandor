import throttle from "lodash.throttle";

((Drupal) => {
  Drupal.behaviors.cloudyBackToTop = {
    attach() {
      const docEl = document.documentElement;
      const btnEl = document.querySelector(".cloudy-back-to-top");
      let minHeightToShow = window.innerHeight * .75;

      window.addEventListener("resize", () => {
        minHeightToShow = window.innerHeight * .75;
      });

      window.addEventListener("scroll", throttle(
        () => {
          const scrollTop = docEl.scrollTop;
          if (scrollTop >= minHeightToShow) {
            btnEl.classList.remove("d-none");
          } else if (scrollTop < minHeightToShow) {
            btnEl.classList.add("d-none");
          }
        },
        300
      ));

      btnEl.addEventListener("click", (e) => {
        e.preventDefault();
        // scroll to x = x, y = 0
        docEl.scrollTo(docEl.scrollLeft, 0);
      });
    }
  }
})(Drupal);
