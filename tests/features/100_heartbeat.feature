@api @multidev @dev
Feature: Basic site operation and navigation
  In order to use portland.gov
  As an anonymous or authenticated user
  I need to be able to navigate from the homepage

  Scenario: Visit the homepage as anonymous user
    When I visit "/"
    Then I should see "Services" in the "header" region
    And I should see "Bureaus and offices" in the "header" region
    And I should see "Alerts" in the "header" region
    And I should see "Portland, Oregon, USA" in the "footer_second" region

  Scenario: Visit the homepage as authenticated user
    Given users:
    | name           | status | uid    | mail                              | roles              |
    | Olive Outsider | 1      | 999997 | olive.outsider@portlandoregon.gov | Authenticated user |
    And I am logged in as "Olive Outsider"
    When I visit "/"
    And I should see "Bureaus and offices" in the "header" region
    And I should see "Alerts" in the "header" region
    And I should see "Portland, Oregon, USA" in the "footer_second" region

  Scenario: Visit a taxonomy term page
    Given I am logged in as "Olive Outsider"
    When I visit "/residents-of-portland"
    Then I should see "Resident"
    And I should see "services found"
    And I should see "Bureaus, offices, and programs"


  # Given users:
  # | name           | status | uid    | mail                              | roles              |
  # | Olive Outsider | 1      | 999997 | olive.outsider@portlandoregon.gov | Authenticated user |
  # | Adam Admin     | 1      | 999998 | adam.admin@portlandoregon.gov     | Authenticated user |
  # | Mary Member    | 1      | 999999 | mary.member@portlandoregon.gov    | Authenticated user |
