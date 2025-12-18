# Address Verifier Widget

portland/portland_address_verifier

This module instantiates a custom composite webform element used to verify street and mailing addresses.

Because there may be address that are not able to be validated due to errors in the PortlandMaps data or new addresses that aren't registered yet, verification is not required by default. A message will be displayed that the user should double check the address but may proceed without verification.

To require verification, mark the Verification Status field required in the element config. In that case, the user may not submit the form without providing a verified address, and the messaging will tell them to try again.

# Use Cases

The Address Verifier handles a number of use cases:

1. Basic address collection, with or without required fields; does not perform any verfication or address lookups, and supports any U.S. street or mailing address (DEFAULT)
    - Individual sub-elements can be made required in the element configuration panel.
    - Make sure the `location_verification_status` sub-element is not required.
    - All address fields visible (address, unit, city, state, zip)

1. Address collection within the Portland metro area, with verification from the PortlandMaps database.
    - To enable and require verification, set the `location_verification_status` sub-element to be required in the element config panel. If not required, non-verified addresses will be accepted. TODO: 
    - Returns UNINCORPORATED for the city name in unincororated areas. To return the postal city for unincorporated areas, set `find_unincorporated: 1` in the custom properties.
    - To allow searching by map, set `use_map: 1`.
    - City/State/Zip fields hidden.

1. Address collection within Portland only (geofenced), with verification from the PortlandMaps database
    - Set the location_verification_status sub-element to be required in the element config panel.
    - Set `require_portland_city_limits: 1` in custom properties.
    - To allow searching by map, set `use_map: 1`.
    - City/State/Zip fields hidden.

1. Collection of lat/lon or x/y location coordinates that may or may not be tied to a specific property or mailing address
    - Set `use_map: 1`.
    - Make sure the `location_verification_status` sub-element is not required.

1. Collection of additional data associated with the location or property ID using supplemental API calls
    - Use the `secondary_queries` property to configure the queries and specify which field will capture the data.

1. Display and/or collection of location coordinates associated with an asset or feature, such as public trash cans or park amenities. This requires use of a custom assets layer.
    - TBD

1. Display of existing reports/tickets, such as open Graffiti reports. 
    - TBD

## Rules

- The first use case, basic address collection without verification, is the only one that allows editable city, state, zip fields.

## Address types

This widget handles both street addresses and mailing addresses, though unit numbers and PO boxes are not verified against any sort of database. Street addresses (those that are associated with a taxlot ID), are verified against the PortlandMaps database. For addresses with a unit number, the base address can be validated, but not the unit number. PO boxes cannot be validated.

TODO: Implement `address_type` property, postal|taxlot

## Architecture and Error Handling

- MVC split: Model runs Suggest/Intersects/secondary queries, parsing, and error routing; View manages UI; Controller wires elements.
- Centralized errors: the Model reports failures through a single handler that logs to Drupal via `AddressVerifierModel.logClientErrorToDrupal` (`/log-api-error`), shows a dialog, and resets verification state.
- Testing: enable `error_test` to force error flows for QA.

## Migration Tips

- Prefer `secondary_queries` over legacy `secondary_query_url/...` for new lookups and captures.
- Use `${x}`/`${y}` placeholders to inject geometry; use `taxlotId` for `detail_id` queries.
- To require verification, set the `location_verification_status` sub-element to Required in the Webform element config.

## Configuration

### Quick Settings Reference

- error_test: 0|1 (default 0) — simulate API errors for testing.
- verify_button_text: string (default "Verify").
- address_suggest: 0|1 (default 1) — enable autocomplete.
- lookup_taxlot: 0|1 (default 0) — fetch taxlotId via Intersects API.
- find_unincorporated: 0|1 (default 0) — use postal city for UNINCORPORATED.
- require_portland_city_limits: 0|1 (default 0) — restrict to Portland.
- out_of_bounds_message: string — message when outside city limits.
- not_verified_heading / not_verified_reasons / not_verified_remedy / not_verified_remedy_required: strings — customize dialogs.
- address_type: street|mailing|any (not yet implemented).
- show_mailing_label: 0|1 (not yet implemented).
- verification_required: OBSOLETE — make `location_verification_status` required instead.
- secondary_query_url / secondary_query_capture_property / secondary_query_capture_field: legacy single-query support.
- secondary_queries: preferred array-based queries; see examples below.

### Custom configuration properties

These custom properties are set in the Advanced tab of the element configuration panel.

**error_test**<br>
When enabled, causes API calls to error out so that error handling can be tested.
Allowed values: 1|0
Default value: 0

**verification_required**<br>
OBSOLETE: Verification can be required by ticking the Required checkbox on the location_verification_status sub-element (#location_verification_status__required)

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

**secondary_query_url - DEPRECATED**<br>
When populated, a second API call is made to the specified API URL with the x/y coordinates passed in the geometry parameter. All 3 properties (secondary_query_url, secondary_query_capture_property, and secondary_query_capture_field) must be set for this to work.

DEPRECATED: Use the secondary_queries array instead.

**secondary_query_capture_property - DEPRECATED**<br>
A dot-notated string that defines the property path to retrieve a value from a nested JSON object. Supports:

- **Nested properties** using dot syntax  
  _Example:_ `zoning.base.0.code` or `features.1.attributes.name`

- **Array access** using numeric indices directly in the path  
  _Example:_ `features.0.attributes.name`

The path must exactly match the structure of the JSON object. Arrays must be accessed using explicit numeric indexes.  
This version does **not** support array mapping with empty brackets (`[]`). If any part of the path is invalid or missing, the function will return `undefined`.

DEPRECATED: Use the secondary_queries array instead.

**secondary_query_capture_field - DEPRECATED**<br>
The ID of the form field into which the captured value should be stored. All 3 properties (secondary_query_url, secondary_query_capture_property, and secondary_query_capture_field) must be set for this to work.

DEPRECATED: Use the secondary_queries array instead.

**verification_required**<br>
OBSOLETE. This custom property is no longer used. To require address verification, set the location_verification_status sub element to be required in the element configuration.

**out_of_bounds_message**<br>
The message displayed if an address is outside the city boundary when require_portland_city_limits is enabled.
Default value: "The address you provided is outside of the Portland city limits. Please try a different address."

**not_verified_heading**<br>
The heading displayed in bold in the not-verified dialog box.
Default value: "We're unable to find this address in the PortlandMaps database."

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
An array of queries to run when an address suggestion is selected or when an address is verified, in addition to built-in geolocation and address suggestion queries. When submitting to the PortlandMaps API, either geometry (x, y) or a detail_id (taxlotId) must be passed. When querying by geometry, pass the string `{"x":${x}, "y":${y}}`. The query functions will replace ${x} and ${y} with the coordinates that were provided by the autocomplete and verification functions. When querying by detail_id, the API expects the taxlotId. Pass an empty string or "taxlotId" as the value, and the function will use the previously retrieved taxlotId value.

See the examples below. 

The path property is dot-notated string that defines the property path to retrieve a value from a nested JSON object. The path must exactly match the structure of the JSON object. If any part of the path is invalid or missing, the function will return `undefined`. Supports:
- **Nested properties** using dot syntax  
  _Example:_ `zoning.base[0].code`
- **Array indexing** with bracket notation  
  _Example:_ `features[2].attributes.name`
- **Mapping over arrays** using empty brackets `[]`, which returns an array of values from each element  
  _Example:_ `features[].attributes.name`

<pre>
secondary_queries:
  - api: 'https://www.portlandmaps.com/api/detail/'
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
  - api: 'https://www.portlandmaps.com/api/detail/'
    api_args:
      - detail_type: environmental-percent-slope
      - sections: general
      - geometry: '{"x":${x}, "y":${y}}'
      - api_key: AC3208DDEFB2FD0AE5F26D573C27252F
    capture:
      - path: 'general.percent_slope'
        field: 'hidden_percent_slope'
  - api: 'https://www.portlandmaps.com/api/detail/'
    api_args:
      - detail_type: hazard-flood
      - sections: general
      - detail_id: taxlotId
      - api_key: AC3208DDEFB2FD0AE5F26D573C27252F
    capture:
      - path: general.percent_slope
        field: q6_project_site_slope</pre>
 