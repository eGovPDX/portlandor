@api @multidev @dev
Feature: Demo feature
  In order to use portland.gov
  As an authenticated user
  I need to be able to navigate from the homepage

  Background: Login as Oliver Outsider
    Given I am logged in as user "oliver.outsider@portlandoregon.gov"

  Scenario: Visit the homepage
    When I visit "/"
    Then I should see "Services"
    And I should see "Alerts"
    And I should see "Search"

  Scenario: Visit a taxonomy term page
    When I visit "/residents-of-portland"
    Then I should see "Resident"
    And I should see "services found"
    And I should see "Bureaus, offices, and programs"
