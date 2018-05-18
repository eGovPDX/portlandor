INTRODUCTION
------------

The Textarea widget for text fields module allows text fields to use the
multiline text area widget on content entry forms.

REQUIREMENTS
------------

None.

RECOMMENDED MODULES
-------------------

The Drupal core 'Text' module can be enabled to use Text (formatted)
fields with a textarea.  The "Text (plain)" field type is always part of
Drupal core, and can also be used with a textarea widget (as of
textarea_widget_for_text 8.x-1.1).

INSTALLATION
------------

* Install as you would any contributed Drupal module. See:
  https://www.drupal.org/documentation/install/modules-themes/modules-8


CONFIGURATION
-------------

* Configure anywhere that fields are configured (content, users, comments,
taxonomy terms, etc).  Add a "Text (formatted)" or "Text (plain)" field if you
don't have one yet.

* Then go to the "Manage form display" tab for that entity, for instance
Administratino » Structure » Content types » Page » Manage form display.

* You can now change the widget of text fields from the default "Textfield" to
"Text area (multiple rows)".
