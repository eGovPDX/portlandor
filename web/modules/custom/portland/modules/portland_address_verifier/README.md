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
Default value: 0

**address_type**
Allows functionality to toggle between street address or mailing address verification, or both, depending on the use case.
Allowed values: street|mailing|any
Default value: "any"
NOTE: This parameter is not yet implemented; currently defaults to "any"

**address_suggest**
Controls whether address suggestions are provided after user starts typing in address field. The default is to enable this
behavior, but it can be disabled in use cases where the address may not be in the Portland area (it can only suggest
addresses that are in the PortlandMaps database).
Allowed values: 1|0
Default value: 1

**show_mailing_label**
Displays the address as it would appear on a mailing label. Can be used for visual inspection of the full address prior to submission.
Allowed values: 1|0
Default value: 0

**find_unincorporated**
Some addresses are technically outside of incorporated areas but are related by zipcode to a nearby city. If this property is true, an additional call is made to the Intersects API to retrieve the zipcode city and use that instead of "UNINCORPORATED."
Allowed values: 1|0
Default value: 0

**secondary_query_url**
When populated, a second API call is made to the specified API URL with the x/y coordinates passed in the geometry parameter. All 3 properties (secondary_query_url, secondary_query_capture_property, and secondary_query_capture_field) must be set for this to work.

**secondary_query_capture_property**
The path of the property to capture from the JSON returned by the secondary_query_url. All 3 properties (secondary_query_url, secondary_query_capture_property, and secondary_query_capture_field) must be set for this to work.

**secondary_query_capture_field**
The ID of the form field into which the captured value should be stored. All 3 properties (secondary_query_url, secondary_query_capture_property, and secondary_query_capture_field) must be set for this to work.
