@javascript
Feature: Site status check
  In order to test that Drupal is running
  As an anonymous user
  I need to be able to see the homepage

  @multidev @dev
  Scenario: Visit the homepage
    Given I am an anonymous user
    When I visit "/"
    # Then print last response
    Then I should see "Portland, Oregon" in the "footer_second" region
    And I should see "Services" in the "primary_menu" region

  @api @dev
  Scenario: Check site status
    Given I am logged in as user "superAdmin" 
    When I visit "/admin/reports/status"
    Then I should not see "Errors found"

  @api @multidev
  Scenario: Check if we need to run "drush entup"
    Given I am logged in as user "superAdmin" 
    When I visit "/admin/reports/status"
    Then I should not see "The following changes were detected in the entity type and field definitions."

  @api @dev
  Scenario: Check config import status
    Given I am logged in as user "superAdmin" 
    When I visit "/admin/config/development/configuration"
    Then I should see "There are no configuration changes to import."