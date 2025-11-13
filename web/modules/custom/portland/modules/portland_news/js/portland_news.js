(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.portland_news = {
    attach: function (context) {
      once('fullSizeImageBehavior', '.news .media--type-image', context).forEach(
        // Add hover class to view image link when hovering over image or link
        function (element) {
          var link = $(element).find('.view-image');

          $(element).find('img').hover(
            function () {
              $(link).addClass('hover');
            },
            function () {
              $(link).removeClass('hover');
            }
          );
          
          $(link).hover(
            function () {
              $(link).addClass('hover');
            },
            function () {
              $(link).removeClass('hover');
            }
          );
        }
      );
    }
  };
})(jQuery, Drupal, drupalSettings);
