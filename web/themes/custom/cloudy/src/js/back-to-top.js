import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.cloudy_back_to_top = {
  attach: function(context, settings) {
    var viewHeight = $(window).height();
    var showHeight = viewHeight * 1.5;
    var isAttached = false;

    $(window).on('scroll', function() {
      var scrollPos = $(document).scrollTop();
      if (scrollPos > showHeight && !isAttached) {
        $('#block-cloudy-content').append('<div id="back-to-top" class="btn btn-dark"><a href="#main-content">Back to top</a></div>');
        isAttached = true;
      } else if (scrollPos <= showHeight && isAttached) {
        $('#back-to-top').remove();
        isAttached = false;
      }
    });

    $('#back-to-top').on('click', function() {
      $(this).remove();
      isAttached = false;
    });
  }
};

