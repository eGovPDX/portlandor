function AddressVerifierController(model, view) {
    this.model = model;
    this.view = view;
}

AddressVerifierController.prototype.init = function() {
    // Initialize the address verifier element
    alert('Initializing Address Verifier!');

    this.view.renderAddressVerifier();
};

AddressVerifierController.prototype.verifyAddress = function(address) {
    this.model.verifyAddress(address);
};

AddressVerifierController.prototype.updateView = function() {
    var verifiedAddress = this.model.getVerifiedAddress();
    this.view.updateAddressUI(verifiedAddress);
};
