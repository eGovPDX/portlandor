# Behat testing
## Drupal interaction
This implementation runs again a multidev instance of the site on Pantheon, and almost all of the features need to run from a terminus authorized machine.
## Configuration
### behat.yml
This file holds all the configuration run during a CircleCI build.
#### Interaction with CircleCI
Circle CI uses some environment variables to add configuration, and the behat.yml file will override that configuration, so be sure to check the CircleCI configiuration before you check in configuration changes to behat.
## Helpful resources
### [Mink context source](https://github.com/Behat/MinkExtension/blob/master/src/Behat/MinkExtension/Context/MinkContext.php)
Use this to see what Gherkin commands you can use with Mink by default.
### [Behat documentation](http://docs.behat.org/en/latest/)
Careful with Google, it loves the 2.5 documentation which is not accurate for the 3.x version we currently use.
