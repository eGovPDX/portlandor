function AddressVerifierView(jQuery, element, model, settings) {
    this.$ = jQuery;
    this.model = model;
    this.settings = settings;
    this.$element = element;
    this.$input = element.find('#location_address');
    this.$suggestModal = element.find('#suggestions_modal');
    this.$statusModal = element.find('#status_modal');
    this.$notFoundModal = element.find("#not_found_modal");
    this.$verificationStatus = element.find('#location_verification_status');
    this.isVerified = false;

    this.$checkmark;
    this.$status;
    this.$button;
}

// globals /////////////////////////////
var $element;
var $input;
var $suggestModal;
var $statusModal;
const NOT_VERIFIED_MESSAGE = "We're unable to verify this address.";
const NOT_VERIFIED_REASONS = "This sometimes happens with new addresses, PO boxes, and multi-family buildings with unit numbers.";
const IF_CERTAIN_MESSAGE = "If you are certain this is the full, correct address, you may use it without verification.";
const MUST_PROVIDE_ADDRESS_MESSAGE = "You must enter an address or partial address to verify.";
const UNVERIFIED_WARNING_MESSAGE = "We're unable to verify this address. If you're certain this is the full, correct address, you may proceed without verification."
const VERIFIED_MESSAGE = "Address is verified!";


AddressVerifierView.prototype.renderAddressVerifier = function () {

    var self = this; // preserve refernece to "this" for use inside functions.

    this._setUpVerifyButton();
    this._setUpInputFieldAndAutocomplete();
    this._setUpUnitNumberField();

};

AddressVerifierView.prototype._setUpVerifyButton = function () {
    var self = this;
    this.$button = this.$('<input>', {
        type: 'button',
        value: self.settings.verify_button_text,
        class: 'button button--primary js-form-submit form-submit btn-verify',
        // display: 'inline-block'
    }).on('click', function (event) {
        // prevent default behavior
        event.preventDefault();

        // display candidates in modal
        // NOTE: Portland Maps API for location suggestions doesn't work property when an ampersand is used to identify intersections
        var address = self.$input.val().replace("&", "and");
        if (address.length >= 3) {
            self._showSuggestions(address);
        } else {
            self._showStatusModal(`<p>${MUST_PROVIDE_ADDRESS_MESSAGE}</p>`);
        }
    });
    this.$input.css({
        display: 'inline-block',
        marginRight: '10px;'
    });

    this.$status = this.$(`<div id="statusMessage" class="status-message invisible"></div>`);
    this.$input.after(this.$status);

    this.$input.after(this.$button);

    this.$checkmark = this.$('<span class="fa fas checkmark invisible"></span>');
    this.$input.after(this.$checkmark);

}

AddressVerifierView.prototype._setUpUnitNumberField = function () {
    var self = this;
    // add onchange handler to unit number field to add unit number to address
    this.$element.find('#unit_number').on('keyup', function (e) {
        var unit = self.$(this).val();
        var $locationData = self.$element.find('#location_data');
        var json = $locationData.val();
        if (json) {
            var item = JSON.parse(json);
            item.unit = unit;
            item.fullAddress = AddressVerifierModel.buildFullAddress(item.street, item.city, item.state, item.zipCode, unit);
            item.displayAddress = item.street + (unit ? " " + unit : "") + ", " + item.city;
            $locationData.val(JSON.stringify(item));
            // self.$element.find('#location_address_label').val(AddressVerifierModel.buildMailingLabel(item, self.$element));
            // self.$element.find('#mailing_label').html(AddressVerifierModel.buildMailingLabel(item, self.$element, true));
        }
    });
}

AddressVerifierView.prototype._setUpInputFieldAndAutocomplete = function () {

    var self = this;

    this.$input.on('keydown', function (e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            self.$button.click();
            return false;
        }
    });

    this.$input.on('keyup', function () {
        if (self.$(this).val().length > 3 && self.isVerified) {
            self._resetVerified(self.$checkmark, self.$button);
        }
    });

    this.$input.autocomplete({
        source: function (request, response) {
            // Call the model method to fetch autocomplete items
            self.model.fetchAutocompleteItems(request.term, self.$element)
                .done(function (locationItems) {
                    // Pass the locationItems to the response callback
                    response(locationItems);
                })
                .fail(function (error) {
                    console.error('Error fetching autocomplete items:', error);
                    response([]);
                });
        }.bind(this),
        minLength: 3,
        select: function (event, ui) {

            self._selectAddress(ui.item);

            return false; // returning true causes the field to be cleared
        },
        response: function (event, ui) {
            const items = ui.content;
            ui.content = items;
        },
        create: function () {
            self.$input.data('ui-autocomplete')._renderItem = function (ul, item) {
                const li = self.$('<li>')
                    .append(item.displayAddress + '  ' + item.zipCode)
                    .appendTo(ul);
                return li;
            };
        }
    });

}

AddressVerifierView.prototype._selectAddress = function (item) {
    var self = this;

    // if the city is unincorporated or the widget is configured to need taxlot ID,
    // we need to perform a call to the intersects API do so some reverse geocoding.
    if ((self.settings.find_unincorporated && (!item.city || item.city.toUpperCase() == "UNINCORPORATED")) || self.settings.lookup_taxlot) {

        if (!item.city || item.city.toUpperCase() == "UNINCORPORATED") {
            self.$element.find('#location_is_unincorporated').val(1);
        }

        // _setVerified is the callback; need to pass self/view reference with it
        this.model.updateLocationFromIntersects(item.lat, item.lon, item, self._setVerified, self);

    } else {
        self._setVerified(item);
    }
}

// this is hte method that handles the location item once its data is complete.
// it sets the location as verified and populates all relevant fields. this function
// may get passed as a callback to the model if supplemental data is required, such
// as city or taxlot ID.
AddressVerifierView.prototype._setVerified = function (item, view = this) {

    // first reset
    view._resetVerified(view.$checkmark, view.$button);

    // NOTE: Suggest API might return a positive match even if the address isn't exactly the same.
    // (e.g. "123 Baldwin St N" vs "123 N Baldwin ST"). If it's not an exact match,
    view.$checkmark.removeClass("invisible").addClass("fa-solid fa-check verified");
    view.$status.text(VERIFIED_MESSAGE).removeClass("invisible").addClass("verified");

    view.$button.prop("disabled", "disabled");
    view.$button.removeClass("button--primary");
    view.$button.addClass("disabled button--info");

    // TODO: if configured, set the taxlot id

    // populate hidden sub-elements for address data
    view.$element.find('#location_address').val(item.street);
    //$element.find('#location_street').val(item.street);
    view.$element.find('#location_address_street_number').val(item.streetNumber);
    view.$element.find('#location_address_street_quadrant').val(item.streetQuadrant);
    view.$element.find('#location_address_street_name').val(item.streetName);
    view.$element.find('#location_city').val(item.city);
    view._setStateByLabel(view, item.state);
    view.$element.find('#location_zip').val(item.zipCode);
    view.$element.find('#location_lat').val(item.lat);
    view.$element.find('#location_lon').val(item.lon);
    view.$element.find('#location_x').val(item.x);
    view.$element.find('#location_y').val(item.y);
    view.$element.find('#location_taxlot_id').val(item.taxlotId);
    // view.$element.find('#location_address_label').val(AddressVerifierModel.buildMailingLabel(item, view.$element));
    // view.$element.find('#mailing_label').html(AddressVerifierModel.buildMailingLabel(item, view.$element, true));
    // view.$element.find('#location_address_label_markup').removeClass('d-none');
    view.$element.find('#location_verification_status').val("Verified");
    view.$element.find('#location_data').val(JSON.stringify(item));
    view.isVerified = true;
    console.log(JSON.stringify(item));
}

AddressVerifierView.prototype._setStateByLabel = function (view, state) {
    // Find the select element
    var element = view.$element.find('#location_state');
    // Find the option with the text matching the full state name
    var option = element.find('option').filter(function () {
        return view.$(this).text().toUpperCase() === state.toUpperCase();
    });
    // Get the value of the found option
    var value = option.val();
    // Set the select list's value to the found value
    view.$element.find('#location_state').val(value);
}

AddressVerifierView.prototype._showSuggestions = function (address) {
    // alert(address);
    var self = this;
    this._resetSuggestModal();
    this.model.fetchAutocompleteItems(address)
        .done(function (locationItems) {

            if (locationItems.length > 0) {
                // Pass the locationItems to the response callback
                var list = self.$("<ul></ul>");
                locationItems.map(function (item) {
                    var strData = JSON.stringify(item);
                    var listItem = self.$(`<li><a href=\"#\" class="pick btn btn-primary"
                        data-item='${strData}'>${item.fullAddress}</a></li>`);
                    listItem.find('a.pick').on('click', function (e) {
                        e.preventDefault();
                        var item = self.$(this).data('item');

                        // user has clicked a suggestion in the modal dialog.......
                        self._selectAddress(item);
                        // now that the user has made a selection, pass back the single candidate
                        // self.$input.val(item.fullAddress);
                        // self._setVerified(self.$checkmark, self.$button, self.$element, item);
                        self.$suggestModal.dialog('close');
                    });
                    list.append(listItem);
                });
                var listInfo = self.$('<p><em>Select one of the verified addresses below.</em></p>');
                self.$suggestModal.append(listInfo);
                var notFound = self.$(`<li><a href=\"#\" class="pick-not-found btn btn-secondary not-found"
                    data-item=''>My address is not listed</a></li>`);
                notFound.find('a.pick-not-found').on('click', function (e) {
                    e.preventDefault();
                    self.$suggestModal.dialog('close');
                    self._showNotFoundModal();
                });
                list.append(notFound);
                self.$suggestModal.append(list);
                Drupal.dialog(self.$suggestModal, {
                    title: 'Possible matches found',
                    width: '600px',
                    buttons: [{
                        text: 'Close',
                        class: 'btn-primary',
                        click: function () {
                            self.$(this).dialog('close');
                        }
                    }]
                }).showModal();
                self.$suggestModal.removeClass('visually-hidden');

            } else if (locationItems.length == 0) {
                // none found, display message
                self._showNotFoundModal();
            }

        })
        .fail(function (error) {
            console.error('Error fetching autocomplete items:', error);
            response([]);
        });
}

AddressVerifierView.prototype._resetSuggestModal = function () {
    this.$suggestModal.html("");
}

AddressVerifierView.prototype._showNotFoundModal = function () {
    var self = this;
    var inputVal = self.$input.val().trim();
    this.$notFoundModal.html(`<p><strong>${NOT_VERIFIED_MESSAGE}</strong> ${NOT_VERIFIED_REASONS}</p><p>${IF_CERTAIN_MESSAGE}</p></p>`);
    Drupal.dialog(this.$notFoundModal, {
        width: '600px',
        buttons: [{
            text: "Okay",
            class: 'btn-default',
            click: function () {
                self.$notFoundModal.dialog('close');
            }
        }]
    }).showModal();
    this.$notFoundModal.removeClass('visually-hidden');
}

AddressVerifierView.prototype._showStatusModal = function (message, buttonText = "Close") {
    var self = this;
    this.$statusModal.html('<p class="status-message mb-0">' + message + '</p>');
    Drupal.dialog(this.$statusModal, {
        width: '600px',
        buttons: [{
            text: buttonText,
            class: 'btn-primary',
            click: function () {
                self.$statusModal.dialog('close');
            }
        }]
    }).showModal();
    this.$statusModal.removeClass('visually-hidden');
}

AddressVerifierView.prototype.testIsMatch = function ($element, item) {
    return item.fullAddress.toUpperCase().startsWith($element.find('#location_address').val().toUpperCase());
}

AddressVerifierView.prototype._resetVerified = function ($checkmark, $button) {
    $checkmark.addClass("invisible").removeClass("fa-triangle-exclamation fa-check verified unverified");
    this.$status.removeClass("verified unverified").addClass("invisible").text("");
    $button.prop('disabled', false);
    $button.removeClass("disabled button--info");
    $button.addClass("button--primary");

    // clear hidden sub-elements for address data
    var $element = this.$(this.element);
    $element.find('#location_address').val("");
    $element.find('#location_address_street_number').val("");
    $element.find('#location_address_street_quadrant').val("");
    $element.find('#location_address_street_name').val("");
    $element.find('#location_city').val("");
    $element.find('#location_state').val("");
    $element.find('#location_zip').val("");
    $element.find('#location_lat').val("");
    $element.find('#location_lon').val("");
    $element.find('#location_x').val("");
    $element.find('#location_y').val("");
    $element.find('#location_taxlot_id').val("");
    $element.find('#location_is_unincorporated').val("");
    // $element.find('#address_label').val("");
    // $element.find('#location_address_label_markup').addClass('d-none');
    $element.find('#location_verification_status').val("");
    $element.find('#location_data').val("");
    // this.$element.find('#container_unit').removeClass('d-none');
    // this.$element.find('#location_address_label_markup').removeClass('d-none');
    this.isVerified = false;
}

// use this function to force an override
AddressVerifierView.prototype._useUnverified = function () {
    this.$checkmark.addClass("invisible");
    this.$status.addClass("invisible");
    // this.$checkmark.removeClass("invisible").addClass("fa-triangle-exclamation unverified");
    this.$status.removeClass("invisible").addClass("unverified").text(UNVERIFIED_WARNING_MESSAGE);
    // var input = this.$notFoundModal.find("#enteredAddress").val();
    // this.$input.val(input);
    this.$button.prop("disabled", "disabled");
    this.$button.removeClass("button--primary");
    this.$button.addClass("disabled button--info");
    this.$element.find('#location_verification_status').val("Forced");
    // this.$element.find('#container_unit').addClass('d-none');
    // this.$element.find('#location_address_label_markup').addClass('d-none');
    this.isVerified = true;
}

// AddressVerifierView.prototype.updateAddressUI = function (address) {
//     // Update the UI with the verified address data
//     alert('Putting the validated address in the UI');
// };

// AddressVerifierView.prototype._getTaxLotNumber = function (item) {
//     var lat = item.lat;
//     var lon = item.lon;
//     this.model.getTaxLotId(lat, lon);
// }

// AddressVerifierView.prototype.handleTaxlotId = function (taxlotId, $element) {
//     $element.find('#location_taxlot_id').val(taxlotId);
//     // alert($element.find('#location_taxlot_id').val());
// }