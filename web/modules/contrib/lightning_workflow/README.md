# Lightning Workflow

Lightning Workflow includes tools for building organization-specific content
workflows using the Workflows and Content Moderation modules in Drupal 8 core.
Out of the box, it gives you the ability to manage content in one of four 
workflow states (draft, needs review, published, and archived). You can create
as many additional states as you like and define transitions between them. It's
also possible to schedule content to be transitioned between states at a
specific future date and time.

### Components
Lightning Workflow's components are not enabled by default. You can install
them like any other Drupal modules.

#### Lightning Scheduler (`lightning_scheduler`)
Provides the ability to schedule workflow state transitions to take place
automatically at a future date and time.

### Installation
This component can only be installed using Composer. To add it to your Drupal
code base:

```
composer config repositories.drupal composer https://packages.drupal.org/8
composer require drupal/lightning_workflow
```

#### Updates
Lightning Workflow and its components use the normal Drupal database update
system as often as possible. However, there are occasionally certain updates
which touch configuration and may change the functionality of your site. These
updates are optional, and are performed by a special utility at the command
line. This utility is compatible with both 
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
