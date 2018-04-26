## OpenAPI Docs Module Documentation
The  OpenAPI Docs (swagger_ui) module provides a visual web UI for browsing REST API documentation. It makes use of the [swagger-ui library](https://github.com/swagger-api/swagger-ui).

Note: at the moment, only the 2.x version of swagger-ui library works with this module.

### Installation - Composer (recommended)
If you're using composer to manage the site (recommended), follow these steps:
 
1. Run `composer require --prefer-dist composer/installers` to ensure that you have the `composer/installers` package installed. This package facilitates the installation of packages into directories other than `/vendor` (e.g. `/libraries`) using Composer.

2. Add the following to the "repositories" section of your project's composer.json:
 ```
-drush en -y swagger_ui
{
  "type": "package",
  "package":{
    "name": "swagger-api/swagger-ui",
    "version": "2.2.10",
    "type": "drupal-library",
    "dist"    : {
      "url": "https://github.com/swagger-api/swagger-ui/archive/v2.2.10.zip",
      "type": "zip"
    },
    "require": {
        "composer/installers": "~1.0"
    }
  }
}
```

3. Add the following to the "installer-paths" section of `composer.json`:
    
 ```
"libraries/{$name}": ["type:drupal-library"],
```

4. Run the following to add the swagger-ui library to your composer.json and download it to /libraries:
```
composer require swagger-api/swagger-ui 2.2.10
```

### Installation - Manual
Extract https://github.com/swagger-api/swagger-ui/archive/v2.2.10.zip into /libraries/swagger-ui
