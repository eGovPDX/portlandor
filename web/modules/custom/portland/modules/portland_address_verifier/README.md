# Address Verifier Widget

portland/portland_address_verifier

This module instantiates a custom composite webform element used to verify street and mailing addresses.

Because there may be address that are not able to be validated due to errors in the PortlandMaps data or new addresses that aren't registered yet, verification is not required by default. A message will be displayed that the user should double check the address but may proceed without verification.

To require verification, mark the Verification Status field required in the element config. In that case, the user may not submit the form without providing a verified address, and the messaging will tell them to try again.

## Address types

This widget handles both street addresses and mailing addresses, though unit numbers and PO boxes are not verified against any sort of database. Street addresses (those that are associated with a taxlot ID), are verified against the PortlandMaps database. For addresses with a unit number, the base address can be validated, but not the unit number. PO boxes cannot be validated.

## Configuration

### Custom configuration properties

These custom properties are set in the Advanced tab of the element configuration panel.

**verify_button_text**<br>
Allows custom text to be used in the address verify button.
Allowed values: [any text]
Default value: "Verify"

**lookup_taxlot**<br>
When true, performs an additional API call to get the taxlot ID number from PortlandMaps.
Allowed values: 1|0
Default value: 0

**address_type**<br>
Allows functionality to toggle between street address or mailing address verification, or both, depending on the use case.
Allowed values: street|mailing|any
Default value: "any"
NOTE: This parameter is not yet implemented; currently defaults to "any"

**address_suggest**<br>
Controls whether address suggestions are provided after user starts typing in address field. The default is to enable this
behavior, but it can be disabled in use cases where the address may not be in the Portland area (it can only suggest
addresses that are in the PortlandMaps database). IMPORTANT NOTE: When suggestions are disabled and the Verify button is
not present, the location_full_address does not get populated. The individual address parts need to be called individually.
Allowed values: 1|0
Default value: 1

**show_mailing_label**<br>
Displays the address as it would appear on a mailing label. Can be used for visual inspection of the full address prior to submission.
Allowed values: 1|0
Default value: 0
NOTE: This parameter is not yet implemented; currently defaults to "any"

**find_unincorporated**<br>
Some addresses are technically outside of incorporated areas but are related by zipcode to a nearby city. If this property is true, an additional call is made to the Intersects API to retrieve the zipcode city and use that instead of "UNINCORPORATED."
Allowed values: 1|0
Default value: 0

**require_portland_city_limits**<br>
When enabled, only allows addresses within Portland city limits.
Allowed values: 1|0
Default value: 0

**secondary_query_url**<br>
When populated, a second API call is made to the specified API URL with the x/y coordinates passed in the geometry parameter. All 3 properties (secondary_query_url, secondary_query_capture_property, and secondary_query_capture_field) must be set for this to work.

**secondary_query_capture_property**<br>
The path of the property to capture from the JSON returned by the secondary_query_url. All 3 properties (secondary_query_url, secondary_query_capture_property, and secondary_query_capture_field) must be set for this to work.

**secondary_query_capture_field**<br>
The ID of the form field into which the captured value should be stored. All 3 properties (secondary_query_url, secondary_query_capture_property, and secondary_query_capture_field) must be set for this to work.

**out_of_bounds_message**<br>
The message displayed if an address is outside the city boundary when require_portland_city_limits is enabled.
Default value: "The address you provided is outside of the Portland city limits. Please try a different address."

**not_verified_heading**<br>
The heading displayed in bold in the not-verified dialog box.
Default value: "We're unable to verify this address."

**not_verified_reasons**<br>
The reason text displayed after the bold not_verified_heading in the not-verified dialog box.
Deafult value: "This sometimes happens with new addresses, PO boxes, and multi-family buildings with unit numbers."

**not_verified_remedy**<br>
The remedy text displayed after the bold not_verified_reasons in the not-verified dialog box. This message is displayed if the Verification Status is NOT required and the form can be submited without a verified address.
Default value: "If you're certain the address is correct, you may use it without verification."

**not_verified_remedy_required**<br>
The remedy text displayed after the bold not_verified_reasons in the not-verified dialog box. This message is displayed if the Verification Status IS required and the form cannot be submitted without a verified address.
Default value: "A verified address is required. Please try again."

**secondary_queries**<br>
An array of queries to run when an address suggestion is selected or when an address is verified, in addition to built-in geolocation and address suggestion queries. When submitting to the PortlandMaps API, either a detail_id (taxlotId) or geometry (x, y) must be passed. See the examples below. 
<pre>
secondary_queries:
  - api: 'https://portlandmaps.com/api/detail/'
    api_args:
      - detail_type: zoning
      - sections: zoning
      - geometry: '{"x":${x}, "y":${y}}'
      - api_key: AC3208DDEFB2FD0AE5F26D573C27252F
    capture:
      - path: 'zoning.overlay[].code'
        field: 'hidden_zoning_overlays'
        parse: stringify
      - path: 'zoning.historic_district[].code'
        field: 'hidden_historic_district'
        parse: stringify
      - path: 'zoning.national_register_district[].code'
        field: 'hidden_national_register_district'
        parse: stringify
      - path: 'zoning.conservation_district[].code'
        field: 'hidden_conservation_district'
        parse: stringify
      - path: 'historic_resource'
        field: 'hidden_historic_resource'
        parse: flatten
        omit_null_properties: true
  - api: 'https://dev.portlandmaps.com/api/detail/'
    api_args:
      - detail_type: environmental-percent-slope
      - sections: general
      - geometry: '{"x":${x}, "y":${y}}'
      - api_key: AC3208DDEFB2FD0AE5F26D573C27252F
    capture:
      - path: 'general.percent_slope'
        field: 'hidden_percent_slope'
</pre>