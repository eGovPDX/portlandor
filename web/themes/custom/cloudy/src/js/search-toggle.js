import $ from 'jquery';
import Drupal from 'Drupal';

function closeSearchResults() {
  // Hide the search drawer when mobile menu toggle is clicked
  const $searchToggle = $('#searchToggle');
  if ($searchToggle.attr('aria-expanded') === 'true') {
    $searchToggle.attr('aria-expanded', 'false');
    // $('.navbar-mobile-search-drawer').toggleClass('show');
    // $(this).parent().parent('.navbar').removeClass('search-is-active');
  }
}

Drupal.behaviors.search_toggle = {
  attach: function(context, settings) {
    $(document, context).once('drawerOpenHandler').on('click', '#searchToggle', function(event) {
      event.preventDefault();
      event.stopPropagation();

      // Hide the mobile drawer when search toggle is clicked
      const $mobileMenuToggle = $('#navToggle');
      if ($mobileMenuToggle.attr('aria-expanded') === 'true') {
        $mobileMenuToggle.addClass('collapsed').attr('aria-expanded', 'false');
        $('#navbarNav').removeClass('show');
			}
    });
  }
};

Drupal.behaviors.mobile_menu_toggle_search_close = {
  attach: function(context, settings) {
    $(document, context).once('drawerCloseHandler').on('click', '#navToggle', function(event) {
      event.preventDefault();
      event.stopPropagation();
			closeSearchResults();

			$(this).find('#navbar-toggle-item--open').toggleClass('d-none');
			$(this).find('#navbar-toggle-item--close').toggleClass('d-block');
    });
  }
};

// TODO: search form close button
Drupal.behaviors.close_search_results = {
  attach: function(context, settings) {
    $(document, context).once('drawerCloseResultsHandler').on('click', '.navbar-mobile-search-drawer .icon', function(event) {
      closeSearchResults();
    });
  }
};
