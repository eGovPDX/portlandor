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
const NOT_VERIFIED_MESSAGE = "The address you entered could not be verified.";
const IF_CERTAIN_MESSAGE = "If you are certain this is the full, correct address, you may use it without verification.";
const MUST_PROVIDE_ADDRESS_MESSAGE = "You must enter an address or partial address to verify.";
const UNVERIFIED_WARNING_MESSAGE = "We're unable to verify this address. Please make sure you've entered the full, correct address before submitting."
const VERIFIED_MESSAGE = "Address is verified!";

AddressVerifierView.prototype.renderAddressVerifier = function () {

    var self = this; // preserve refernece to "this" for use inside functions.

    this._setUpVerifyButton();
    this._setUpInputFieldAndAutocomplete();

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

    this.$checkmark = this.$('<span class="fa fas checkmark invisible" title="Location is verified!"></span>');
    this.$input.after(this.$checkmark);

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
            self._resetUnverified(self.$checkmark, self.$button);
        }
    });

    this.$input.autocomplete({
        source: function (request, response) {
            // Call the model method to fetch autocomplete items
            self.model.fetchAutocompleteItems(request.term)
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
            self.$input.val(ui.item.fullAddress);
            self.$input.autocomplete('close');
            self._setVerified(self.$checkmark, self.$button, self.$element, ui.item);
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
                        // now that the user has made a selection, pass back the single candidate
                        self.$input.val(item.fullAddress);
                        self._setVerified(self.$checkmark, self.$button, self.$element, item);
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
    this.$notFoundModal.html(`<p><strong>${NOT_VERIFIED_MESSAGE}</strong></p><p>${IF_CERTAIN_MESSAGE}</p><p><input type="text" id="enteredAddress" class="form-text form-control" value="${inputVal}"></p>`);
    Drupal.dialog(this.$notFoundModal, {
        width: '600px',
        buttons: [{
            text: "Use this address",
            class: 'btn-primary',
            click: function () {
                self.$notFoundModal.dialog('close');
                self._useUnverified()
            }
        }, {
            text: "Cancel",
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

AddressVerifierView.prototype._setVerified = function ($checkmark, $button, $element, item) {
    // NOTE: Suggest API might return a positive match even if the address isn't exactly the same.
    // (e.g. "123 Baldwin St N" vs "123 N Baldwin ST"). If it's not an exact match,
    $checkmark.removeClass("invisible").addClass("fa-solid fa-check verified");
    this.$status.text(VERIFIED_MESSAGE).removeClass("invisible").addClass("verified");

    $button.prop("disabled", "disabled");
    $button.removeClass("button--primary");
    $button.addClass("disabled button--info");

    // populate hidden sub-elements for address data
    $element.find('#location_address').val(item.fullAddress);
    $element.find('#location_street').val(item.street);
    $element.find('#location_street_number').val(item.streetNumber);
    $element.find('#location_street_quadrant').val(item.streetQuadrant);
    $element.find('#location_street_name').val(item.streetName);
    $element.find('#location_city').val(item.city);
    $element.find('#location_state').val(item.state);
    $element.find('#location_zip').val(item.zipCode);
    $element.find('#location_lat').val(item.lat);
    $element.find('#location_lon').val(item.lon);
    $element.find('#location_x').val(item.x);
    $element.find('#location_y').val(item.y);
    $element.find('#location_address_label').val(AddressVerifierModel.buildMailingLabel(item));
    this.isVerified = true;
    console.log(JSON.stringify(item));
}

AddressVerifierView.prototype._resetUnverified = function ($checkmark, $button) {
    $checkmark.addClass("invisible").removeClass("fa-triangle-exclamation fa-check verified unverified");
    this.$status.removeClass("verified unverified").addClass("invisible").text("");
    $button.prop('disabled', false);
    $button.removeClass("disabled button--info");
    $button.addClass("button--primary");

    // clear hidden sub-elements for address data
    var $element = this.$(this.element);
    $element.find('#verified_address').val("");
    $element.find('#location_street').val("");
    $element.find('#location_street_number').val("");
    $element.find('#location_street_quadrant').val("");
    $element.find('#location_street_name').val("");
    $element.find('#location_city').val("");
    $element.find('#location_state').val("");
    $element.find('#location_zip').val("");
    $element.find('#location_lat').val("");
    $element.find('#location_lon').val("");
    $element.find('#location_x').val("");
    $element.find('#location_y').val("");
    $element.find('#address_label').val("");
    this.isVerified = false;
}

AddressVerifierView.prototype._useUnverified = function () {
    this.$checkmark.removeClass("invisible").addClass("fa-triangle-exclamation unverified");
    this.$status.removeClass("invisible").addClass("unverified").text(UNVERIFIED_WARNING_MESSAGE);
    var input = this.$notFoundModal.find("#enteredAddress").val();
    this.$input.val(input);
    this.$button.prop("disabled", "disabled");
    this.$button.removeClass("button--primary");
    this.$button.addClass("disabled button--info");
    this.isVerified = true;
}

AddressVerifierView.prototype.updateAddressUI = function (address) {
    // Update the UI with the verified address data
    alert('Putting the validated address in the UI');
};

