# Rain: Pattern Lab + Drupal 8

Component-driven prototyping tool using [Pattern Lab v2](http://patternlab.io/) automated via Gulp/NPM. Provides a way to hook Drupal templates to a pattern lab implmentation and publish a starterkit.

## Requirements

  1. [Node](https://nodejs.org)
  2. [Gulp](http://gulpjs.com/)
  3. [Composer](https://getcomposer.org/)

## Quickstart (Rain Standalone)
Install with NPM:
`npm install`

Compile the CSS:
`npm run drupal:build`
or
watch the css for changes:
`npm start`

Start Pattern Lab:
`npm run pl:start`

## Drupal-specific installation

### In a Composer-based Drupal install (recommended)

  1. Add the dependencies: `composer require drupal\components drupal\unified_twig_ext`
  2. Enable Rain and its dependencies `drush en -y rain components unified_twig_ext`

## Starting Pattern Lab and watch task

For now, you will need to run two separate tasks to watch your pattern's Sass files and run Pattern Lab:

  1. `npm start`
  2. `npm run pl:start`

## Why Pattern Lab?
Pattern Lab is a tool for rapidly developing, prototyping, and testing UI pieces for complex web applications.  It allows you to create templates that take inputs, and test the ways that a UI pattern should behave given different sets of inputs.  It also gives you an easy way to publish and share those patterns with your stakeholders and even the world at large.
### Pattern Lab's structure
There are four different pieces to a pattern lab:
1. The template engine (Twig, Mustache, etc.)
2. The Pattern Lab core (PHP or Node)
3. The starterkit (the patterns)
4. The styleguidekit

A combination of all four of these elements is called an edition.

<img src="https://user-images.githubusercontent.com/30271981/41615447-29d29da8-73b0-11e8-94ad-701a5d4fa379.png" width="600" title="Github Logo">

### Sharing patterns
Patterns developed by one team can easily be shared as a starterkit with the world. To do that, all you need is to commit your patterns into a dist folder in your repository on Github.  Once you have done that, you can install a starterkit in your Pattern Lab project using the Pattern Lab CLI (`php core/console --starterkit --install [starterkit-name]` in this PHP version)
### Pattern Lab's asset gap
Pattern lab provides a way to test HTML templates for a couple of different engines (i.e., Twig and Mustache). It does not proscribe a way to style those templates or add javascript functionality.  This needs to be done by the developer, and as such has led to everyone coming up with their own build process that works for them on their project.
## Pattern Lab + Drupal
### Required modules
#### Component Library
The [component module](https://www.drupal.org/project/components) creates Twig namespaced paths, essentially a dictionary of shortcuts and paths that are configured in your themes info yaml file.  This is the only truly required module, as it allows you to quickly point Drupal templates to a pattern lab folder.
#### Unified Twig Ext
This module provides a way to add functions that you place with your patterns into Drupal's Twig extention registry.  This is not strictly required but this project uses it for the bem function to help create classes.
### Using patterns from a template
Once there are patterns to use that are prototyped and tested, hooking them into Drupal is done using the Twig `include` or `extend` tags in your normal Twig template folder structure like so:
```
{% include "@molecules/branding/01-brand.twig" with {
  "url": path('<front>'),
  "brand_prefix": "The",
  "brand_main": "City of Portland",
  "brand_suffix": "Oregon",
} %}
```
You can call this pattern with the confidence of having prototyped and tested that this will work, without having to test through Drupal and running cache rebuilds endlessly.  Neat!
## This project's pattern structure choices
There is no proscribed way for structuring patterns, so for this project we have come up with three options for structure:
| Atomic    | Water     | Component  |
|:---------:|:---------:|:----------:|
| base      | base      | utilities  |
| atoms     | drops     | base       |
| molecules | puddles   | elements   |
| organisms | ponds     | components |
| templates | templates | layouts    |
| pages     | pages     | pages      |
## Project Roadmap
* Webpack-based build process
  * Use Webpack for bundling any javascript created
  * Sass compiling should be folded into that process so there is only one build process
* Look at how to better use Twig functions between Pattern Lab and Drupal so there isn't reliance on naming conventions
