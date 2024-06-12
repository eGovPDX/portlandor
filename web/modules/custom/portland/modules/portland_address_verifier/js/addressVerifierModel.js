function AddressVerifierModel() {}

AddressVerifierModel.prototype.verifyAddress = function(address) {
    // Call the third-party API to verify the address
    // Update verifiedAddress with the verified address data
    alert('Doing verification now');
};

AddressVerifierModel.prototype.getVerifiedAddress = function() {
    return "123 Fake St, Anytown, USA 10101";
};
