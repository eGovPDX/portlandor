
// jQuery include for WaterWorks project

$(document).ready(function() {

	// autofocus search field if search button is clicked
	$('#MainHead .search, #DetailHead .search').click(function() {
		$('#AddressSearch').focus();
	});

	// on window resize, recalculate isMobileView
	$(window).resize(function () {
		isMobileView = calculateIsMobileView();
	});

	// enter-key handler for address search input
	$('#AddressSearch').keypress(function (e) {
		if (e.which == 13) {
			$('.searchSubmit').click();
			return false;
		}
	});

	$('.searchSubmit').val('test');

});


