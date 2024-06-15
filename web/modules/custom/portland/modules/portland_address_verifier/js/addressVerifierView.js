
function AddressVerifierView(jQuery, element, model, settings) {
    this.$ = jQuery;
    this.model = model;
    this.settings = settings;

    this.$element = element;
    this.$input = element.find('#location_address');
    this.$suggestModal = element.find('#suggestions_modal');
    this.$statusModal = element.find('#status_modal');
}

// globals /////////////////////////////
var $element;
var $input;
var $suggestModal;
var $statusModal;

AddressVerifierView.prototype.renderAddressVerifier = function () {

    var self = this; // preserve refernece to "this" for use inside functions.

    // Add verify button ///////////////////////////////////////////
    var $button = this.$('<input>', {
        type: 'button',
        value: self.settings.verify_button_text,
        class: 'button button--primary js-form-submit form-submit btn-verify'
    }).on('click', function (event) {
        // prevent default behavior
        event.preventDefault();

        // display candidates in modal
        // NOTE: Portland Maps API for location suggestions doesn't work property when an ampersand is used to identify intersections
        var address = self.$input.val().replace("&", "and");
        if (address.length >= 3) {
            self._addressSearch(address);
        } else {
            self._showStatusModal("You must enter an address or partial address to verify.");
        }
    });

    this.$input.after($button);
    this.$input.css({
        display: 'inline-block',
        marginRight: '10px;'
    });
    $button.css({
        display: 'inline-block'
    })

    //set up pick links
    this.$(document).on('click', 'a.pick', function (e) {
        e.preventDefault();
        var item = self.$(this).data('item');
        // now that the user has made a selection, pass back the single candidate
        self.$input.val(item.fullAddress);
        self._setVerified($checkmark, $button, self.$element, item);
        self.$suggestModal.dialog('close');
    });

    // Add verified checkmark ///////////////////////////////////
    var $checkmark = this.$('<span class="verified-checkmark address invisible" title="Location is verified!">✓</span>');
    this.$input.after($checkmark);

    // Configure address field ////////////////////////////////////

    // disable form submit when pressing enter; force user to use autocomplete or verify button
    this.$input.on('keydown', function (e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            $button.click();
            return false;
        }
    });

    // unset verify if address field value changes
    this.$input.on('keyup', function () {
        if (self.$(this).val().length > 3) {
            self._setUnverified($checkmark, $button);
        }
    });

    // set up autocomplete
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
            self._setVerified($checkmark, $button, self.$element, ui.item);
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
    })

};

AddressVerifierView.prototype._addressSearch = function (address) {
    // alert(address);
    var self = this;
    this._resetSuggestModal();
    this.model.fetchAutocompleteItems(address)
        .done(function (locationItems) {
            // Pass the locationItems to the response callback
            var list = self.$("<ul></ul>");
            locationItems.map(function (item) {

                var strData = JSON.stringify(item);
                var listItem = self.$(`<li><a href=\"#\" class="pick"
                    data-item='${strData}'>${item.fullAddress}</a></li>`);
                list.append(listItem);

            });
            self.$suggestModal.append(list);
            Drupal.dialog(self.$suggestModal, {
                title: 'Multiple possible matches found',
                width: '600px',
                buttons: [{
                    text: 'Close',
                    click: function () {
                        self.$(this).dialog('close');
                    }
                }]
            }).showModal();
            self.$suggestModal.removeClass('visually-hidden');
        })
        .fail(function (error) {
            console.error('Error fetching autocomplete items:', error);
            response([]);
        });
}

AddressVerifierView.prototype._resetSuggestModal = function () {
    this.$suggestModal.html("");
}

AddressVerifierView.prototype._showStatusModal = function (message) {
    var self = this;
    this.$statusModal.html('<p class="status-message mb-0">' + message + '</p>');
    Drupal.dialog(this.$statusModal, {
        width: '600px',
        buttons: [{
            text: 'Close',
            click: function () {
                self.$statusModal.dialog('close');
            }
        }]
    }).showModal();
    this.$statusModal.removeClass('visually-hidden');
}

AddressVerifierView.prototype._setVerified = function ($checkmark, $button, $element, item) {
    $checkmark.removeClass("invisible");
    $button.prop("disabled", "disabled");
    $button.removeClass("button--primary");
    $button.addClass("disabled button--info");

    // populate hidden sub-elements for address data
    $element.find('#verified_address').val(item.fullAddress);
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
}

AddressVerifierView.prototype._setUnverified = function ($checkmark, $button) {
    $checkmark.addClass("invisible");
    $button.prop('disabled', false);
    $button.removeClass("disabled button--info");
    $button.addClass("button--primary");

    // clear hidden sub-elements for address data
    var $element = this.$(this.element);
    $element.find('#verified_address').val("");
    $element.find('#location_street').val("");
    $element.find('#location_street_number').val();
    $element.find('#location_street_quadrant').val();
    $element.find('#location_street_name').val();
    $element.find('#location_city').val("");
    $element.find('#location_state').val("");
    $element.find('#location_zip').val("");
    $element.find('#location_lat').val("");
    $element.find('#location_lon').val("");
    $element.find('#location_x').val("");
    $element.find('#location_y').val("");
}

AddressVerifierView.prototype.updateAddressUI = function (address) {
    // Update the UI with the verified address data
    alert('Putting the validated address in the UI');
};

