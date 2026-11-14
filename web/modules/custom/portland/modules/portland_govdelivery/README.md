Portland GovDelivery
====================

This submodule provides Portland-specific GovDelivery integration.

Admin settings
- The admin settings page is at /admin/config/services/portland-govdelivery
- View:
  - API base URL (configure manually in config file, uses config split)
  - GovDelivery account code (configure manually in config file, same for all environments)
- Configure:
  - Username key ID (select a Key ID managed by the Keys module)
  - Password key ID (select a Key ID managed by the Keys module)
- Utilities:
  - GovDelivery status panel (resolved API base URL, connection test)
  - GovDelivery topics list panel (read-only)
  - Subscribe a user panel

Notes
- The username and password values remain in the Keys module; this form only selects which key IDs to use.
- After configuring, use the test page or queue a subscription using the provided service.
