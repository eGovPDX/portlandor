@api @javascript @multidev @dev
Feature: Demo feature
  In order to use portland.gov
  As an authenticated user
  I need to be able to navigate from the homepage

  Scenario: Visit the homepage
    Given I am logged in as user "oliver.outsider@portlandoregon.gov"
    When I visit "/"
    Then I should see "Services"
    And I should see "Search"

  Scenario: Visit a taxonomy term page
    Given I am logged in as user "oliver.outsider@portlandoregon.gov"
    When I visit "/residents-of-portland"
    Then I should see "Resident"
    And I should see "services found"
    And I should see "Bureaus, offices, and programs"
