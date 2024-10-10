(function ($, Drupal) {
    Drupal.behaviors.pensionCalculatorBehavior = {
        attach: function (context, settings) {

			/* Stormwater Discounts Calculator Object */
			var swd = {
				// Calculate fixed monthly charge for services to streets and watersheds
				calculateStreets: function () {
					var value = 21.10;
					value = Math.round(value*100) / 100;		// Round to two decimal places
					return value;
				},

				// Calculate fixed monthly charge for on-site private property services
				calculateOnsite: function () {
					var value = 32.45;
					value = Math.round(value*100) / 100;		// Round to two decimal places
					value = value - this.calculateStreets();
					return value;
				},

				// Calculate total fixed monthly charge
				calculateGrossCharge: function () {
					console.log('calculateGrossCharge', this.calculateStreets() + this.calculateOnsite())
					return this.calculateStreets() + this.calculateOnsite();
				},

				// Calculate total discount (percentage of total discount)
				calculateTotalDiscount: function () {
					var offProperty, drywells, landscape, cisterns, pond, ecoroof, imperviousArea, trees;
					offProperty = $('input[name="off_property"]').prop('checked');
					drywells = $('input[name="drywells"]').prop('checked');
					landscape = $('input[name="landscape"]').prop('checked');
					cisterns = $('input[name="cisterns"]').prop('checked');
					pond = $('input[name="pond"]').prop('checked');
					ecoroof = $('input[name="ecoroof"]').prop('checked');
					imperviousArea = $('input[name="impervious_area"]').prop('checked');
					trees = $('input[name="trees"]').prop('checked');

					var fullOnsite, partialOnsite, onsiteDetention, smallLotCredit, treeCredit, raw, total;

					if (drywells || landscape || cisterns || ecoroof) {
						if (offProperty || pond ) {
							fullOnsite = 0;
						} else {
							fullOnsite = 1;
						}
					} else {
						fullOnsite = 0;
					}
					// console.info("Full Onsite %f", fullOnsite);

					if (drywells || landscape || cisterns || ecoroof) {
						if (offProperty || pond ) {
							partialOnsite = 0.67;
						} else {
							partialOnsite = 0;
						}
					} else {
						partialOnsite = 0;
					}
					// console.info("Partial Onsite %f", partialOnsite);

					if (pond) {
						onsiteDetention = 0.67;
					} else {
						onsiteDetention = 0;
					}
					// console.info("Onsite detention %f", onsiteDetention);

					if (imperviousArea) {
						smallLotCredit = 0.25;
					} else {
						smallLotCredit = 0;
					}
					// console.info("Small lot %f", smallLotCredit);

					if (trees) {
						treeCredit = Math.round(4*50/2400 * 100) / 100;		// Round to two decimal places
					} else {
						treeCredit = 0;
					}
					// console.info("Trees %d", treeCredit);

					raw = Math.max(fullOnsite, partialOnsite);
					raw = Math.max(raw, onsiteDetention);
					raw += smallLotCredit +  treeCredit;
					// console.info("Raw %d", raw);

					total = Math.min(raw, 1);
					// console.info("Total %f", total);

					return total;
				},

				// Calculate discount qualified for based off of user selected checkboxes
				calculateDiscount: function (e) {
					if (e) {
						e.preventDefault();
					}

					var totalDiscount = this.calculateTotalDiscount();
					console.log('totalDiscount', totalDiscount);
					
					var discountAmt = this.calculateOnsite() * totalDiscount;
					discountAmt = Math.round(discountAmt * 100) / -100;		// Round to two decimal places
					console.log('discountAmt', discountAmt);
					$('input[name="discount"]').val( formatNumber(discountAmt, "$,.00") );

					var netCharge = this.calculateGrossCharge() + discountAmt;
					console.log('netCharge', netCharge);
					$('input[name="net_charge"]').val( formatNumber(netCharge, "$,.00") );
				},

				showPercentage: function () {
					var totalDiscount = this.calculateTotalDiscount();
					totalDiscount = formatNumber(totalDiscount, "###%");

					alert("This amount equals " + totalDiscount + " of the on-site stormwater charge.");
				}

			};


			// Link calculatePension() to button click
			$('#edit-calculate-submit').click(function (e) {
				swd.calculateDiscount(e);
			});

			// Set fixed montly charges
			$('input[name="streets"]').val( formatNumber(swd.calculateStreets(), "$,.00") );
			$('input[name="onsite"]').val( formatNumber(swd.calculateOnsite(), "$,.00") );
			$('input[name="gross_charge"]').val( formatNumber(swd.calculateGrossCharge(), "$,.00") );

			swd.calculateDiscount();
        }
    };
})(jQuery, Drupal);
