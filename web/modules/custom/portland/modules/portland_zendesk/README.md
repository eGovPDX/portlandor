# portland/portland_zendesk
This module is an extension of the strakez/zendesk-webform module. That module is a non-standard,
unofficial Drupal module, so we chose to knock it off and customize in place, rather than fork or
patch the original.

## Conventions used in association with this module

### Agent Use Only blocks in webforms

Any webform that 311 agents might complete on behalf of community members has a Customer Service block consisting
of two fields: Employee Email and Zendesk Request Number. The logged in agent's name and email address are automatically
inserted into the Agent Email field. If there is an associated "interaction request" to track the agent's interaction
with the community member, that request number is inserted in the other field when completing the form in order to
create the "issue request." The issue request is typcially created using the ZendeskHandler custom webform handler. 
Each form with a AUO block also has a ZendeskUpdateHandler that updates the initial interaction request to link
the two together. This is done by chaining the handlers as described below.

The webform Zendesk API Test is maintained as a template for how this functionality should be configured.

NOTE: The terms "ticket" and "request" have the same meaning in Portland.gov and may be used interchangeably in contexts
that will not be seen by community members. However, all public-facing verbiage should refer to tickets as "requests," 
and avoid use of the term "agent." 

### Chaining handlers

It's possible to chain together an instance of the ZendeskHandler and ZendeskUpdateHandler in order
to use the new request number from the first handler as a token or custom field value in the second handler.
To accomplish this, the field in the first ZendeskHandler that captures the new request number must be
named report_ticket_id. If so, the token [webform_submission:values:report_ticket_id] will be avaialble to 
any subsequent handlers for use in comments, custom fields, etc.

Portland.gov is currently using this functionality to link interaction and issue tickets when
a 311 agent completes a webform on behalf of a community member. The workflow is as follows:

1. Agent creates an "interaction request" to track the initial interaction with the community member (phone, in person, email, etc).
2. Agent completes and submits the webform, and includes the request number from the interaction request in the Zendesk Request Number field.
3. Since the Zendesk submissions occur during the validation phase, so that API failures are reported as validation errors, the request number of the request created by the initial ZendeskHandler call has to be manually inserted into the $webform_submission object in order to make it available as a token in subsequent handlers.




# Original README follows...
# strakez/zendesk-webform
Add a webform handler to create Zendesk tickets from Drupal webform submissions.

## Installation
With [composer/installers](https://github.com/composer/installers) in effect, Drupal packages are installed to their own specified paths. However the default 
configs for Drupal packages don't include custom modules. We'll need to add one:

Add the following to the `extra.installer-paths` object:
```text
"web/modules/custom/{$name}": ["type:drupal-custom-module"],
```

Then, run the following command in your terminal to require this package:
```bash
composer require strakez/zendesk-webform
```


## Setup

### 1) Get a Zendesk API Key

Please see the following link for instructions on [retrieving your Zendesk API Key](https://support.zendesk.com/hc/en-us/articles/226022787-Generating-a-new-API-token-).

### 2) Activate the Module

- Activate the Zendesk Webform module from your site's Extend page.

### 4) Configure the Zendesk Connection Settings
- Navigate to the configuration page (found under ***Configuration -> System -> Zendesk Integration Form***), and fill out the required fields. (Note: your API key will be used here.)

### 3) Add a Zendesk Handler to a Webform

- Navigate to the desired webform's ***Settings -> Email/Handlers*** page, and click **Add Handler**.
- Specify settings for the Zendesk request to be created.

### 4) Test

It is recommend to submit a test submission to confirm your settings. If the request is created in Zendesk as desired, 
congrats! You've successfully setup up the handler integration.

## Additional Notes

### Store Zendesk Ticket ID

This module can help to keep track of the Zendesk Ticket ID directly on each submission. You'll need to create a hidden field when building the form, and then set it as the Zendesk Ticket ID Field in the handler configuration form.