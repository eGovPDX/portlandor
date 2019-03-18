@javascript
Feature: Demo feature
  In order to test Drupal
  As site admin
  I need to verify the site status is valid

  Background: Login as admin
    Given I am logged in as user "superAdmin" 

  @api @dev
  Scenario: Check site status
    When I visit "/admin/reports/status"
    Then I should not see "Errors found"

  @api @multidev
  Scenario: Check if we need to run "drush entup"
    When I visit "/admin/reports/status"
    Then I should not see "The following changes were detected in the entity type and field definitions."

  @api @dev
  Scenario: Check config import status
    When I visit "/admin/config/development/configuration"
    Then I should see "There are no configuration changes to import."