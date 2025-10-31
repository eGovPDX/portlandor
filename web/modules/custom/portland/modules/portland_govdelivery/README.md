Portland GovDelivery
====================

This submodule provides Portland-specific GovDelivery integration.

Admin settings
- The admin settings page is at /admin/config/services/portland-govdelivery
- Configure:
  - GovDelivery account code
  - Username key ID (select a Key ID managed by the Keys module)
  - Password key ID (select a Key ID managed by the Keys module)

Notes
- The username and password values remain in the Keys module; this form only selects which key IDs to use.
- After configuring, use the test page or queue a subscription using the provided service.
