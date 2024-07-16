/**
 * @file
 * Back to top button.
 *
 * Display back to top button at viewheight * 1.5.
 */
(function ($, Drupal) {
  function waitForElementToExist(selector) {
    return new Promise(resolve => {
      if (document.querySelector(selector)) {
        return resolve(document.querySelector(selector));
      }

      const observer = new MutationObserver(() => {
        if (document.querySelector(selector)) {
          resolve(document.querySelector(selector));
          observer.disconnect();
        }
      });

      observer.observe(document.body, {
        subtree: true,
        childList: true,
      });
    });
  }

  Drupal.behaviors.cloudyExpandAllAccordion = {
    attach: function (context, settings) {
      waitForElementToExist('div.aria-accordion').then(element => {
        var accordion = $(element);
        accordion.prepend('<p class="toggle-accordian-text text-end d-block mb-1"><a href="javascript:void(0)" class="toggle-accordion"></a></p>')

        accordion.delegate(
          'a.toggle-accordion',
          'click',
          function () {
            var toggleControl = $(this);
            toggleControl.toggleClass('active'),
              toggleControl.hasClass('active') ? (
                accordion.find('.aria-accordion__heading > button').attr('aria-expanded', 'true'),
                accordion.find('div.aria-accordion__panel').attr("hidden", false)
              ) : (
                accordion.find('.aria-accordion__heading > button').attr('aria-expanded', 'false'),
                accordion.find('div.aria-accordion__panel').attr("hidden", true)
              )
          }
        );
      });

    }
  };
})(jQuery, Drupal);
