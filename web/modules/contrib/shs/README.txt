
-- SUMMARY --

The "Simple hierarchical select" module displays selected taxonomy fields as
hierarchical selects on entity forms and as exposed filter in views.


-- REQUIREMENTS --

The module "Taxonomy" (Drupal core) needs to be enabled for SHS to work
properly.


-- INSTALLATION --

* Install as usual, see https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.


-- CONFIGURATION --

* Create a new field ("Reference > Taxonomy term") and select
  "Simple hierarchical select" on the form display settings for the new field.

* Form display settings
  - "Allow creating new items" (disabled)
    Items may be created directly from within the widget (user needs to have
    permission to create items in the configured bundle).
  - "Allow creating new levels" (disabled)
    Users with permission to create items in the configured bundle will be
    able to create a new item as child of the currently selected item.
  - "Force selection of deepest level"
    Force users to select items from the deepest level.


-- INTERGRATION WITH OTHER MODULES --

 * Views (Drupal core)
   - You are able to use the Simple hierarchical select widget as an exposed
     filter in Views.
     Simply add a filter for your term reference field or a term reference
     filter ("Has taxonomy terms" or "Has taxonomy  terms (with depth)") in your
     view and select "Simple hierarchical select" as selection type.
 * Chosen
   To use the Chosen library (https://www.drupal.org/project/chosen) you need to
   enable "Simple hierarchical select: Chosen" (shs_chosen) and configure the
   form display of your field to use Chosen.


-- MISSING FEATURES --

* Form display settings:
  Some options are current disabled because the underlying features are not
  implemented yet. This will be done until version 8.x-1.0.


-- CONTACT --

Current maintainers:
* Stefan Borchert (stborchert) - http://drupal.org/user/36942

This project has been sponsored by:
* undpaul
  Drupal experts providing professional Drupal development services.
  Visit http://www.undpaul.de for more information.

