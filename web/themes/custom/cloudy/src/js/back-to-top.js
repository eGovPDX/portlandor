import $ from 'jquery';
//import Drupal from 'Drupal';


var viewHeight = $(window).height();
var showHeight = viewHeight * 2;
var isAttached = false;

$(window).on('scroll', function() {
  var scrollPos = $(document).scrollTop();
  if (scrollPos > showHeight && !isAttached) {
    $('#block-cloudy-content').append('<div id="back-to-top"><a href="#main-content">Back to top</a></div>');
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