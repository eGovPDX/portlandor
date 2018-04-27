## Lightning Layout

Lightning Layout provides modules and configuration for great layout authoring
experiences. It uses the [Panels](https://drupal.org/project/panels) In-Place
Editor (IPE) to enable a drag-and-drop experience when laying out pages.

### Components
Lightning Layout's components are not enabled by default. You can install them
like any other Drupal modules.

#### Landing Page (`lightning_landing_page`)
Provides a minimal "Landing page" content type which is configured out of the 
box to be customizable using the
[Panelizer](https://drupal.org/project/panelizer) module and visually editable
using the drag-and-drop Panels In-Place Editor.

### Installation
This component can only be installed using Composer. To add it to your Drupal
code base:

```
composer config repositories.drupal composer https://packages.drupal.org/8
composer require drupal/lightning_layout
```

#### Updates
Lightning Layout and its components use the normal Drupal database update system
as often as possible. However, there are occasionally certain updates which
touch configuration and may change the functionality of your site. These updates
are optional, and are performed by a special utility at the command line. This 
utility is compatible with both 
[Drupal Console](https://github.com/hechoendrupal/drupal-console) and
[Drush](https://drush.org) 9 or later.

To run updates using Drush 9:

`
drush update:lightning
`

With Drupal Console:

`
drupal update:lightning
`

#### Known Issues
None yet.