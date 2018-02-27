Views Reference Field
=====================

INTRODUCTION
------------
The Views Reference Field works the same way as any Entity Reference field
except that the entity it targets is a View
You can target a View using the Entity Reference field
but you cannot nominate a particular View display
The Views Reference Field enables you to nominate a display ID and an argument


REQUIREMENTS
------------
You will need the `config_filter` module to be enabled.

INSTALLATION
------------
Install the module as usual
Or use:
/*****  Composer *****/
Although Views Reference does not need composer,
if you install using composer then use the following:

From the drupal root directory of your install:

composer config repositories.drupal composer https://packages.drupal.org/8
composer require drupal/viewsreference

CONFIGURATION
-------------
In any entity in the Manage fields tab:
When adding new fields a Views Reference field will now be available

After adding a viewsreference field are the following additional settings:
View display plugins to allow
so you can limit the Views plugin types that can be accessed from the field
Preselect view options

MAINTAINERS
-----------
Current maintainers:

 * Kent Shelley (New Zeal) - https://www.drupal.org/u/new-zeal
 * Joe Kersey (joekers) - https://www.drupal.org/u/joekers
