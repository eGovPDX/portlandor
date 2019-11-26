jQuery(window).bind('pageshow', function() {
  // On pageshow, reset form to restore search field value to original search term
  var form = jQuery('#search-api-page-block-form')[0].reset(); 
});