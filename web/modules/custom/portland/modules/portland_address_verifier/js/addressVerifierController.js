function AddressVerifierController(element, model, view) {
    this.element = element;
    this.model = model;
    this.view = view;
}

AddressVerifierController.prototype.init = function() {
    // Initialize the address verifier element
    this.view.renderAddressVerifier();
};

AddressVerifierController.prototype.verifyAddress = function(address) {
    this.model.verifyAddress(address);
};

AddressVerifierController.prototype.updateView = function() {
    var verifiedAddress = this.model.getVerifiedAddress();
    this.view.updateAddressUI(verifiedAddress);
};
