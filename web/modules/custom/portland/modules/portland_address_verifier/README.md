# Address Verifier Widget

portland/portland_address_verifier

This module instantiates a custom composite webform element used to verify street and mailing addresses.

## Address types

This widget handles both street addresses and mailing addresses, though unit numbers and PO boxes are not verified against any sort of database. Street addresses (those that are associated with a taxlot ID), are verified against the PortlandMaps database. For addresses with a unit number, the base address can be validated, but not the unit number. PO boxes cannot be validated.

## Configuration

### Custom configuration properties

These custom properties are set in the Advanced tab of the element configuration panel.

**verify_button_text**
Allows custom text to be used in the address verify button.
Allowed values: [any text]
Default value: "Verify"

**lookup_taxlot**
When true, performs an additional API call to get the taxlot ID number from PortlandMaps.
Allowed values: 1|0
Default value: "0"

**address_type**
Allows functionality to toggle between street address or mailing address verification, or both, depending on the use case.
Allowed values: street|mailing|any
Default value: "any"
NOTE: This parameter is not yet implemented; currently defaults to "any"

**show_mailing_label**
Displays the address as it would appear on a mailing label. Can be used for visual inspection of the full address prior to submission.
Allowed values: 1|0
Default value: "0"