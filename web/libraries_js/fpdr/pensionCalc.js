(function ($, Drupal) {
    Drupal.behaviors.pensionCalculatorBehavior = {
        attach: function (context, settings) {
            // Calculates the estimated pension
            function calculatePension(e) {
                e.preventDefault();

                // Get values from form fields
                var hMonth = parseInt(  $('#edit-hire-date-month').val() );
                var hYear = parseInt( $('#edit-hire-date-year').val() );
                var rMonth = parseInt(  $('#edit-retire-date-month').val() );
                var rYear = parseInt( $('#edit-retire-date-year').val() );
                var ppPay = parseFloat( strip( $('#edit-pp-pay').val(), "$,") );
                var accrual = parseFloat( $('#edit-accrual').val() );

                var checkHiredBefore = (hMonth == 7 && hYear == 1995);

                // Validate input
                var errors = new Array();
                if (isNaN(hMonth)) {
                    errors.push("You must select the month you were hired.");
                }
                if (isNaN(hYear)) {
                    errors.push("You must select the year you were hired.");
                }
                if (checkHiredBefore && !$('#edit-hired-before-yes').is(":checked") && !$('#edit-hired-before-no').is(":checked")) {
                    errors.push("You must select whether or not you were hired before July 14, 1995.");
                }
                if (isNaN(rMonth)) {
                    errors.push("You must select the month you will retire.");
                }
                if (isNaN(rYear)) {
                    errors.push("You must select the year you will retire.");
                }
                if (isNaN(ppPay) || ppPay <= 0) {
                    errors.push("You must enter a valid value for pensionable pay per pay period.");
                }
                if (isNaN(accrual)) {
                    errors.push("You must select your accrual percentage.");
                }

                // Validation failed
                if (errors.length) {
                    var errorMsg = "Please correct the following error(s):\n";
                    for (i = 0; i < errors.length; i++)
                        errorMsg += "* " + errors[i] + "\n";
                    alert(errorMsg);
                    return;
                }

                var hiredDate = new Date(hYear, hMonth-1);
                if (checkHiredBefore) {
                    var hiredBefore = $('#hiredBeforeYes').is(":checked") ? true : false;
                } else {
                    var forkDate = new Date(1995, 6);			// 6 = July b/c month range is 0-11
                    var hiredBefore = hiredDate < forkDate;
                }

                var serviceYears = (rYear+rMonth/12) - (hYear+hMonth/12);		// Total length of service in years
                serviceYears = Math.min(serviceYears, 30);		// Cap length of service at 30 years

                if (rYear % 4 == 0 && rMonth > 2)
                    var payPeriods = 26.14;
                else if ((rYear-1) % 4 == 0 && rMonth < 3)
                    var payPeriods = 26.14;
                else
                    var payPeriods = 26.07;

                var finalPay = ppPay * payPeriods / 12;

                if (!hiredBefore)		// Hired after July 14, 1995
                    var offset = 0;
                else if (serviceYears < 10)
                    var offset = 0;
                else if (serviceYears < 20)
                    var offset = 0.01;
                else if (serviceYears < 25)
                    var offset = 0.025;
                else
                    var offset = 0.04;

                var baseBenefit = finalPay * serviceYears * accrual;
                var taxOffset = baseBenefit * offset;
                var pensionEstimate = baseBenefit + taxOffset;

                // Update result fields
                $('#edit-service-years').val( formatNumber(serviceYears, ".##") );
                $('#edit-base-benefit').val( formatNumber(baseBenefit, "$,.00") );
                $('#edit-tax-offset').val( formatNumber(taxOffset, "$,.00") );
                $('#edit-pension-estimate').val( formatNumber(pensionEstimate, "$,.00") );
                $('#edit-calculated-pension-results').show();
            }

            // Only show hiredBefore field when hire date is July 1995
            function showHideHiredBefore() {
                var hMonth = $('#edit-hire-date-month').val();
                var hYear = $('#edit-hire-date-year').val();

                if (hMonth == 7 && hYear == 1995)
                    $('#edit-hired-before--wrapper').show();
                else
                    $('#edit-hired-before--wrapper').hide();
            };

            // Initialize hiredBefore field and add change listeners
            showHideHiredBefore();
            $('#edit-hire-date-month,#edit-hire-date-year').change(showHideHiredBefore);

            $('#edit-calculated-pension-results').hide();

            // Link calculatePension() to button click
            $('#edit-actions-submit').click(calculatePension);
        }
    };
})(jQuery, Drupal);
