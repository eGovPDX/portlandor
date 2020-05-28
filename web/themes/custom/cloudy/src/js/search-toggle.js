import $ from 'jquery';
import Drupal from 'Drupal';

Drupal.behaviors.search_toggle = {
  attach: function() {
    $(document).on('click', '.navbar-toggle-group .search-toggle', function(event) {
      event.preventDefault();
      event.stopPropagation();

      // Hide the mobile drawer when search toggle is clicked
      const $mobileMenuToggle = $('.navbar-toggle-group .navbar-toggler');
      if ($mobileMenuToggle.attr('aria-expanded') === 'true') {
        $mobileMenuToggle.addClass('collapsed').attr('aria-expanded', 'false');
        $('.navbar-mobile-drawer').removeClass('show');
      }

      $(this).parent('.navbar-toggle-group').siblings('.navbar-mobile-search-drawer').toggleClass('show');
      $(this).attr('aria-expanded', $(this).attr('aria-expanded') === 'false' ? 'true' : 'false');
      $(this).parent().parent('.navbar').toggleClass('search-is-active');
    });
  }
};

Drupal.behaviors.mobile_menu_toggle_search_close = {
  attach: function() {
    $(document).on('click', '.navbar-toggle-group .navbar-toggler', function(event) {
      event.preventDefault();
      event.stopPropagation();

      // Hide the search drawer when mobile menu toggle is clicked
      const $searchToggle = $('.navbar-toggle-group .search-toggle');
      if ($searchToggle.attr('aria-expanded') === 'true') {
        $searchToggle.attr('aria-expanded', 'false');
        $('.navbar-mobile-search-drawer').removeClass('show');
        $(this).parent().parent('.navbar').removeClass('search-is-active');
      }
    });
  }
};
