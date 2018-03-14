# Lightning API
Lightning API provides a standard API with authentication and authorization
that allows for easy ingestion of content by other applications. It primarily
makes use of the json:api and OAuth2 standards via the
[JSON API](https://www.drupal.org/project/jsonapi) and
[Simple Oauth](https://www.drupal.org/project/simple_oauth) modules.

### Installation
This component can only be installed using Composer. To add it to your Drupal
code base:

```
composer config repositories.drupal composer https://packages.drupal.org/8
composer require drupal/lightning_api
```

#### Updates
Lightning API and its components use the normal Drupal database update system
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
* For security reasons, the JSON API module does not support creating or
  modifying config entities via the API.
