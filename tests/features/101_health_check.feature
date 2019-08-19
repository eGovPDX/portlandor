@api @javascript @multidev @dev @101
Feature: Site health check
  In order to test that site is healthy and no imports are pending
  As a sitewide administrator
  I need to be able to check the site status

  Background:
    Given I am using a 1440x900 browser window

  # NOTE: These tests use a real user against the real site/database.
  # Login is handled using drush in FeatureContext.
  Scenario: Check site status
    Given I am logged in as user "superAdmin" 
    When I visit "/admin/reports/status"
    Then I should not see "Errors found"

  Scenario: Check if we need to run "drush entup"
    Given I am logged in as user "superAdmin" 
    When I visit "/admin/reports/status"
    Then I should not see "The following changes were detected in the entity type and field definitions."

  Scenario: Check config import status
    Given I am logged in as user "superAdmin" 
    When I visit "/admin/config/development/configuration"
    Then I should see "There are no configuration changes to import."