import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.search_toggle = {
  attach: function() {
    $(document).on('click', '.navbar-toggle-group .search-toggle', function(event) {
      event.preventDefault();
      event.stopPropagation();

      const target = $(this);
      $(target).parent('.navbar-toggle-group').siblings('.navbar-mobile-search-drawer').toggleClass('show');
      $(target).attr('aria-expanded', $(target).attr('aria-expanded') === 'false' ? 'true' : 'false');
      $(target).parent().parent('.navbar').toggleClass('search-is-active');
    });
  }
};
