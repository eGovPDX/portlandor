/**
 * @file
 * Back to top button.
 *
 * Display back to top button at viewheight * 1.5.
 */
(function($, Drupal) {
  Drupal.behaviors.cloudyBackToTop = {
    attach: function(context, settings) {
      var viewHeight = $(window).height();
      var showHeight = viewHeight * 1.5;
      var isAttached = false;

      $(once('backToTopShowButtonHandler', window)).each(function () {
          $(this).on('scroll', function() {
          var scrollPos = $(document).scrollTop();
          if (scrollPos > showHeight && !isAttached) {
            var buttonText = Drupal.t('Back to top');
            $('#block-cloudy-content', context).append('<a id="back-to-top" class="btn btn-dark" href="#header">' + buttonText + '</a>');
            isAttached = true;
          } else if (scrollPos <= showHeight && isAttached) {
            $('#back-to-top').remove();
            isAttached = false;
          }
        });
      });

      $(once('backToTopClickHandler', '#back-to-top', context)).each(function () {
          $(this).on('click', function() {
          $(this).remove();
          isAttached = false;
        });
      });
    }
  };
})(jQuery, Drupal);
