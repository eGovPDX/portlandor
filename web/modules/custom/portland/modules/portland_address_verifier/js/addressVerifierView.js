function AddressVerifierView(jQuery, element, model, settings) {
    this.$ = jQuery;
    this.model = model;
    this.settings = settings;
    this.$element = element;
    this.$input = element.find('#location_address');
    this.$suggestModal = element.find('#av_suggestions_modal');
    this.$statusModal = element.find('#status_modal');
    this.$notFoundModal = element.find("#not_found_modal");
    this.$verificationStatus = element.find('#location_verification_status');
    this.isVerified = false;
    this._serverError = false;

    // this.$checkmark;
    // this.$status;
    // this.$button;
}

// globals /////////////////////////////
// var $element;
// var $input;
// var $suggestModal;
// var $statusModal;
const MUST_PROVIDE_ADDRESS_MESSAGE = "You must enter an address or partial address to verify.";
const UNVERIFIED_WARNING_MESSAGE = "We're unable to verify this address. If you're certain this is the full, correct address, you may proceed."
const VERFICATION_REQUIRED_MESSAGE = "Address verification is required, but we're unable to verify this address. Please try again.";
const VERIFIED_MESSAGE = "Address found!";
const SERVER_ERROR_MESSAGE = "There was an problem connecting to our location services. Please check the <a href=\"/\" target=\"_blank\">Portland.gov homepage</a> for maintenance or outage alerts, or try again later.";
const INPUT_FIELDS = [
    '#location_address',
    '#location_city',
    '#location_state',
    '#location_zip'
]; // these fields accept user input and need to be monitored for changes
const HIDDEN_FIELDS = [
    '#location_full_address',
    '#location_address_street_number',
    '#location_address_street_quadrant',
    '#location_address_street_name',
    '#location_address_street_type',
    '#location_jurisdiction',
    '#location_lat',
    '#location_lon',
    '#location_x',
    '#location_y',
    '#location_taxlot_id',
    '#location_is_unincorporated',
    '#location_data'
]; // these fields are set programmatically and should be cleared when the user changes the address
const IGNORE_FIELDS = [
    '#unit_number',
]; // these fields can be ignored for the purposes of address verification

AddressVerifierView.prototype.renderAddressVerifier = function () {
    if (this.settings && this.settings.address_suggest) {
        this._setUpVerifyButton();
        this._setUpInputFieldAndAutocomplete();
    }
    this._setUpUnitNumberField();
    // this._handlePostback();
};

AddressVerifierView.prototype._handlePostback = function () {
    var self = this;

    // if address fields are populated on postback, automatically activate verification action
    const $locationAddress = this.$element.find('#location_address');
    const $locationState = this.$element.find('#location_city');
    const $locationCity = this.$element.find('#location_state');
    const $locationZip = this.$element.find('#location_zip');

    if ($locationAddress.val() && $locationCity.val() && $locationState.val() && $locationZip.val()) {
        this.model.fetchAutocompleteItems($locationAddress.val())
            .done(function (locationItems) {
                if (locationItems.length == 1) {
                    // if single item, auto verify
                    const item = locationItems[0];
                    self._selectAddress(item);
                } else if (locationItems.length > 1) {
                    self.showSuggestionListInModal(locationItems);
                } else {
                    // no results, show not found modal
                    self._showNotFoundModal();
                }

            });
    }

    AddressVerifierView.prototype.showSuggestionListInModal = function (locationItems) {
        var self = this;

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

                self.$suggestModal.dialog('close');
            });
            list.append(listItem);
        });
        var listInfo = self.$('<p><em>Select one of the addresses below.</em></p>');
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

    }

    requestAnimationFrame(function () {
        const verificationStatus = self.$verificationStatus.val();

        if (verificationStatus === "Verified") {
            self.isVerified = true;

            self.$checkmark.removeClass("invisible").addClass("fa-solid fa-check verified");
            self.$status.text(VERIFIED_MESSAGE).removeClass("invisible").addClass("verified");
            self.$button.prop("disabled", "disabled");
            self.$button.removeClass("button--primary");
            self.$button.addClass("disabled button--info");

            // Bind handlers now that DOM and values are stable
            for (let i = 0; i < INPUT_FIELDS.length; i++) {
                const field = INPUT_FIELDS[i];
                self.$element.find(field).off('input.portland change.portland').on('input.portland change.portland', function () {
                    if (self.isVerified) {
                        self._resetVerified(self.$checkmark, self.$button);
                    }
                });
            }
        } else if (verificationStatus === "Unverified") {
            self.isVerified = false;
            self.$checkmark.removeClass("invisible").addClass("fa-triangle-exclamation unverified");
            self.$status.text(UNVERIFIED_WARNING_MESSAGE).removeClass("invisible").addClass("unverified");
        }
    });
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
        var address = self.$input.val().replace(/&/g, "and");
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
            item.displayAddress = item.street + (unit ? ", " + unit : "") + ", " + item.city;
            $locationData.val(JSON.stringify(item));
            self.$element.find('#location_full_address').val(item.fullAddress);
        }
    });
}

AddressVerifierView.prototype._setUpInputFieldAndAutocomplete = function () {

    var self = this;

    this.$input.on('keydown', function (e) {
        if (e.keyCode == 13) {
            // only trigger on enter if an element in the autocomplete menu isn't active/focused, otherwise it will happen twice
            if (self.$input.autocomplete('widget') && self.$input.autocomplete('widget').has('.ui-state-active').length) return;
            
            e.preventDefault();
            self.$button.click();
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
                    // response([]);
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
        focus: function (event, ui) {
            // if triggered by keyboard navigation, populate the input with the focused item's address
            if (event.originalEvent && event.originalEvent.originalEvent && /^key/.test(event.originalEvent.originalEvent.type)) {
                self.$input.val(ui.item.displayAddress + ' ' + ui.item.zipCode);
                return false;
            }
        },
        create: function () {
            self.$input.data('ui-autocomplete')._renderItem = function (ul, item) {
                const li = self.$('<li>')
                    .append(`<div>${item.displayAddress} ${item.zipCode}</div>`)
                    .appendTo(ul);
                return li;
            };
        }
    });

}

AddressVerifierView.prototype._selectAddress = function (item) {
    var self = this;

    // DEFAULT ADDRESS VERIFIER SETTINGS:
    //      address_suggest = 1                 Suggest addresses in autocomplete and Verify button results.
    //      lookup_taxlot = 0                   Do not lookup taxlot ID.
    //      find_unincorporated = 1             Use the postal city.
    //      require_portland_city_limits = 0    Allow addresses in any jurisdiction.
    //      verification_required = false       Verification not required by default. This is set in the element configuration.

    // The City value returned by the Suggest API is the postal city associated with the zipcode.
    // The Jurisdiction value returned by the Suggest API is the governance entity (e.g., Portland, Gresham, Unincorporated).

    // This needs to cover 3 main use cases:
    // 1. Require verified City of Portland address.
    // 2. Allow any address anywhere, including unincorporated areas. (DEFAULT)
    // 3. Verification is required, but address can be anywhere that's in the PortlandMaps database, including unincorporated areas.

    // Use case 1 is the only one that sets the item.city with the value from item.jurisdiction.
    // Additional logic can be built into the form using conditional fields or computed twig, 
    // and individual city/state/zip fields can be required in the element configuration.

    if ((self.settings.require_portland_city_limits && self.settings.verification_required) || !self.settings.find_unincorporated) {
        // USE CASE 1: only allow verified addresses within Portland city limits.
        // use jurisdiction value as city, then verify jurisdiction/city is PORTLAND in _setVerified.
        item.city = item.jurisdiction.toUpperCase();
    }
    
    // USE CASE 2: allow any address anywhere, including unincorporated areas. 
    // if (self.settings.find_unincorporated && !self.settings.require_portland_city_limits && !self.settings.verification_required)
    // do nothing here--item.city is already set to postal city by Suggest API.
    // city/state/zip can be required in the element configuration.

    // USE CASE 3: verification is required, but address can be anywhere that's 
    // if (self.settings.verification_required)
    // in the PortlandMaps database, including unincorporated areas.
    // do nothing here--item.city is already set to postal city by Suggest API.
    
    // need to get taxlot? requires call to intersects API.
    if (self.settings.lookup_taxlot) {
        // _setVerified is the callback; need to pass self/view reference with it
        this.model.updateLocationFromIntersects(item.lat, item.lon, item, self._setVerified, self);
    } else {
        self._setVerified(item);
    }

    // regardless of what we're using in the city field, set unincorporated flag if applicable
    if (item.jurisdiction.toUpperCase() == "UNINCORPORATED") {
        self.$element.find('#location_is_unincorporated').val(1);
    }

    // if configured, run secondary query // url, x, y, callback, view
    if (this.settings.secondary_query_url && this.settings.secondary_query_capture_property && this.settings.secondary_query_capture_field) {
        this.model.callSecondaryQuery(this.settings.secondary_query_url, item.x, item.y, self._processSecondaryResults, self, this.settings.secondary_query_capture_property, this.settings.secondary_query_capture_field, this.$);
    }

    // blur address field after setting
    //self.$element.find('#location_address').blur();
}

AddressVerifierView.prototype._processSecondaryResults = function (results, view = this, capturePath, captureField, $) {
    // get property value from results as indicated by path (can we access this.settings here?)
    var propertyValue = AddressVerifierModel.getPropertyByPath(results, capturePath);
    view.$element.find('#' + captureField).val(propertyValue).trigger('change');
}

AddressVerifierView.prototype._processSecondaryResultsNew = function (results, view, query) {
    if (query.capture && query.capture.length > 0) {
        for (var i = 0; i < query.capture.length; i++) {
            let field = query.capture[i].field;
            let path = query.capture[i].path;
            let parse = query.capture[i].parse;
            let omit_nulls = query.capture[i].omit_null_properties;

            // this returns non-stringified JSON object or empty string
            let propertyValue = AddressVerifierModel.getPropertyByPathNew(results, path, parse, omit_nulls);

            view.$('input[name="' + field + '"]').val(propertyValue).trigger('change');
        }
    }
}

// this method is called when an address is selected in the autocomplete list or in the
// list of options displayed by the Verify button. it does the following:
// * clears all fields, both visible and hidden
// * if invalid location, show error modal
// * parses the location data sets the values of all fields
// * adds change handlers to the visible user input fields (calls _resetVerified)
// * makes the "verified" visual indicators visible
// * sets the internal isVerified flag to true
// * if secondary_queries are configured, run them at the end of this method
AddressVerifierView.prototype._setVerified = function (item, view = this) {
    view.isVerified = false; // reset to false until we know it's verified

    // show error modal if invalid location /////////////////////////
    if (view.settings.require_portland_city_limits && item.jurisdiction.toUpperCase() != "PORTLAND") {
        view._showOutOfBoundsErrorModal(item.fullAddress);
        view.$element.find('#location_address').val('');
        return false;
    }

    // parse location data and set field values, add change handlers to visible input fields /////////////////////////
    // visible input fields
    view.$element.find('#location_address').off('input.portland change.portland').val(item.street.toUpperCase()).trigger('change').blur().on('input.portland change.portland', function () {
        view._resetVerified(view.$checkmark, view.$button);
    });
    view.$element.find('#location_city').off('input.portland change.portland').val(item.city.toUpperCase()).trigger('change').on('input.portland change.portland', function () {
        view._resetVerified(view.$checkmark, view.$button);
    });
    view._setStateByLabel(view, item.state);
    view.$element.find('#location_zip').off('input.portland change.portland').val(item.zipCode).trigger('change').on('input.portland change.portland', function () {
        view._resetVerified(view.$checkmark, view.$button);
    });

    // hidden data fields
    view.$element.find('#location_full_address').val(item.fullAddress.toUpperCase());
    view.$element.find('#location_address_street_number').val(item.streetNumber);
    view.$element.find('#location_address_street_quadrant').val(item.streetQuadrant.toUpperCase());
    view.$element.find('#location_address_street_name').val(item.streetName.toUpperCase());
    view.$element.find('#location_address_street_type').val(item.streetType.toUpperCase());
    view.$element.find('#location_jurisdiction').val(item.jurisdiction.toUpperCase());
    view.$element.find('#location_lat').val(item.lat);
    view.$element.find('#location_lon').val(item.lon);
    view.$element.find('#location_x').val(item.x);
    view.$element.find('#location_y').val(item.y);
    view.$element.find('#location_taxlot_id').val(item.taxlotId);
    view.$element.find('#location_data').val(JSON.stringify(item));

    // show visual "verified" indicators /////////////////////////
    view.$checkmark.removeClass("invisible").addClass("fa-solid fa-check verified");
    view.$status.text(VERIFIED_MESSAGE).removeClass("invisible").addClass("verified");
    view.$button.prop("disabled", "disabled");
    view.$button.removeClass("button--primary");
    view.$button.addClass("disabled button--info");

    // set internal isVerified flag to true /////////////////////////
    view.isVerified = true;
    view.$element.find('#location_verification_status').val("Verified").trigger('change');
    view.$element.find('.invalid-feedback').addClass('d-none');

    // hide validation message
    view.$element.find('#location_address_label_markup').removeClass('d-none');
    view.$element.find('.error').removeClass('error');

    if (view.settings.secondary_queries) {
        view._runSecondaryQueries(item);
    }
}

AddressVerifierView.prototype._runSecondaryQueries = function (item) {
    var self = this;

    for (let i = 0; i < this.settings.secondary_queries.length; i++) {
        let query = this.settings.secondary_queries[i];

        if (query.api) {
            // new method using array of secondary queries
            // there may not be args or captures, just a URL we need to hit, such as for testing error codes or health checks
            let queryUrl = query.api + "?format=json";
            if (query.api_args && query.api_args.length > 0) {
                for (const arg of query.api_args) {
                    const [key, value] = Object.entries(arg)[0];

                    switch (key) {
                        case 'geometry':
                            queryUrl += "&geometry=" + value.replace('${x}', item.x).replace('${y}', item.y);
                            break;
                        case 'detail_id':
                            queryUrl += "&detail_id=" + item.taxlotId;
                            break;
                        default:
                            queryUrl += "&" + key + "=" + encodeURIComponent(value);
                    }
                }
            }

            // TODO: This call belongs in the model, not the view
            this.$.ajax({
                url: queryUrl,
                success: function (results, textStatus, jqXHR) {
                    if (textStatus == "success" && (!results.status || results.status && results.status == "success" && !self.settings.error_test)) {
                        self._processSecondaryResultsNew(results, self, query);
                    } else {
                        const message = "API call failed";
                        const error = new Error();
                        self._logError(jqXHR, SERVER_ERROR_MESSAGE, results?.status || message, error);
                        self._displayError(self, jqXHR, SERVER_ERROR_MESSAGE, results?.status || message);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    const message = "API call failed";
                    const error = new Error(); // captures accurate stack location
                    self._logError(jqXHR, textStatus, jqXHR?.responseText, error);
                    self._displayError(self, jqXHR, textStatus, jqXHR?.responseText);
                }
            });

        } else {
            // old method using single secondary query settings
            if (query.url && query.capture_property && query.capture_field) {
                this.model.callSecondaryQuery(query.url, self.$element.find('#location_x').val(), self.$element.find('#location_y').val(), self._processSecondaryResults, self, query.capture_property, query.capture_field, this.$);
            }
        }
    }
}

AddressVerifierView.prototype._displayError = function (view, jqXHR, textStatus, errorThrown) {
    var statusCode = jqXHR ? jqXHR.status : '';
    textStatus = textStatus == "error" ? "Unknown error" : textStatus;
    var message = (statusCode ? statusCode + " " : "") + (errorThrown ? errorThrown : textStatus ? textStatus : "Unknown error");
    view._showStatusModal(`<p>${SERVER_ERROR_MESSAGE}<br><br>Status: ${message}</p>`, "Close");
    view._resetVerified(view.$checkmark, view.$button);
    view._serverError = 1;
}

AddressVerifierView.prototype._logError = function (jqXHR, textStatus, message, errorObj) {
    let file = 'unknown';
    let line = 0;
    const stack = errorObj?.stack || '';

    // Try to extract file and line number from second stack frame
    const match = stack.split('\n')[1]?.match(/(https?:\/\/[^\s:]+):(\d+):(\d+)/);
    if (match) {
        file = match[1];
        line = parseInt(match[2]);
    }

    const status = jqXHR?.status || 'Unknown';
    const statusText = jqXHR?.statusText || 'No status text';
    const responseText = jqXHR?.responseText || '';

    const fullMessage = `${message} (${status} ${statusText}): ${textStatus}`;

    AddressVerifierModel.logClientErrorToDrupal(
        fullMessage,
        file,
        line,
        "Response:\n" + responseText
    );
};

AddressVerifierView.prototype._setStateByLabel = function (view, state) {
    var self = view;

    // Find the select element
    var element = view.$element.find('#location_state');
    // Find the option with the text matching the full state name
    var option = element.find('option').filter(function () {
        return view.$(this).text().toUpperCase() === state.toUpperCase();
    });
    // Get the value of the found option
    var value = option.val();
    // Set the select list's value to the found value
    view.$element.find('#location_state').val(value).off('input.portland change.portland').trigger('change').on('input.portland change.portland', function () { view._resetVerified(view.$checkmark, view.$button); });
}

AddressVerifierView.prototype._showSuggestions = function (address) {
    // alert(address);
    var self = this;
    this._resetSuggestModal();
    this.model.fetchAutocompleteItems(address)
        .done(function (locationItems) {

            if (locationItems.length > 0) {
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
    var remedyMessage = this.settings.verification_required ? this.settings.not_verified_remedy_required : this.settings.not_verified_remedy;
    this.$notFoundModal.html(`<p><strong>${this.settings.not_verified_heading}</strong> ${this.settings.not_verified_reasons}</p><p>${remedyMessage}</p></p>`);
    Drupal.dialog(this.$notFoundModal, {
        width: '600px',
        buttons: [{
            text: "Okay",
            class: 'btn-default',
            click: function () {
                self.$notFoundModal.dialog('close');
                // if verification is not required, set the status to "Forced"
                if (!self.settings.verification_required) {
                    self._useUnverified();
                }
            }
        }]
    }).showModal();
    this.$notFoundModal.removeClass('visually-hidden');
}

AddressVerifierView.prototype._showOutOfBoundsErrorModal = function (address) {
    var self = this;
    var inputVal = self.$input.val().trim();
    this.$notFoundModal.html(`<p><strong>${this.settings.out_of_bounds_message}</strong></p><p>${address}</p>`);
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

// this method is called when the user changes the value of any of the visible input fields
// or prior to setting the fields and marking as verified. it does the following:
// * clear values of hidden data fields
// * remove change handlers from visible input fields
// * hide the "verified" visual indicators
// * set internal isVerified flag to false
AddressVerifierView.prototype._resetVerified = function ($checkmark, $button) {

    // clear values of hidden data fields /////////////////////////
    for (var i = 0; i < HIDDEN_FIELDS.length; i++) {
        var field = HIDDEN_FIELDS[i];
        this.$element.find(field).val("");
    }

    // verification status field is handled separately
    // if a status is passed to this method, use it in the status field
    this.$element.find("#location_verification_status").val("").trigger('change');

    // remove change handlers from visible input fields /////////////////////////
    for (var i = 0; i < INPUT_FIELDS.length; i++) {
        var field = INPUT_FIELDS[i];
        this.$element.find(field).off('input.portland change.portland');
    }

    // hide the "verified" visual indicators /////////////////////////
    $checkmark.addClass("invisible").removeClass("fa-triangle-exclamation fa-check verified unverified");
    this.$status.removeClass("verified unverified").addClass("invisible").text("");
    $button.prop('disabled', false);
    $button.removeClass("disabled button--info");
    $button.addClass("button--primary");

    // set internal isVerified flag to false /////////////////////////
    this.isVerified = false;
}

// use this function to force an override
AddressVerifierView.prototype._useUnverified = function () {
    this.$checkmark.addClass("invisible");
    this.$status.addClass("invisible");
    // this.$checkmark.removeClass("invisible").addClass("fa-triangle-exclamation unverified");
    var unverifiedMessage = this.settings.verification_required ? VERFICATION_REQUIRED_MESSAGE : UNVERIFIED_WARNING_MESSAGE;
    this.$status.removeClass("invisible").addClass("unverified").text(unverifiedMessage);
    // var input = this.$notFoundModal.find("#enteredAddress").val();
    // this.$input.val(input);
    this.$button.prop("disabled", "disabled");
    this.$button.removeClass("button--primary");
    this.$button.addClass("disabled button--info");
    this.$element.find('#location_verification_status').val("Forced").trigger('change');
    // this.$element.find('#container_unit').addClass('d-none');
    // this.$element.find('#location_address_label_markup').addClass('d-none');
    this.isVerified = true;
}
