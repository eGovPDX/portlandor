@javascript
Feature: Demo feature
  In order to test Drupal
  As an anonymous user
  I need to be able to see the homepage

  @multidev
  Scenario: Visit the homepage
    Given I am an anonymous user
    When I visit "/"
    # Then print last response
    Then I should see "Portland, Oregon"

  @api @dev
  Scenario: Check site status
    Given I am logged in as user "superAdmin" 
    When I visit "/admin/reports/status"
    Then I should not see "Errors found"

  @api @dev
  Scenario: Check config import status
    Given I am logged in as user "superAdmin" 
    When I visit "/admin/config/development/configuration"
    Then I should see "There are no configuration changes to import."