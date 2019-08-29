@api @javascript @multidev @dev @100
Feature: Basic site operation and navigation
  In order to use portland.gov
  As an anonymous or authenticated user
  I need to be able to navigate from the homepage

  Background:
    Given I am using a 1440x900 browser window

  Scenario: Visit the homepage as anonymous user
    When I visit "/"
    Then I should see "Services" in the "main_menu" region
    And I should see "Bureaus and offices" in the "main_menu" region
    And I should see "Alerts" in the "main_menu" region
    And I should see "Portland, Oregon, USA" in the "footer_second" region

  Scenario: Visit the homepage as authenticated user
    Given I am logged in as user "oliver.outsider@portlandoregon.gov"
    When I visit "/"
    Then I wait for the page to be loaded
    And I should see "Bureaus and offices" in the "main_menu" region
    And I should see "Alerts" in the "main_menu" region
    And I should see "Portland, Oregon, USA" in the "footer_second" region

    # Given users:
    # | name           | status | uid    | mail                              | roles              |
    # | Olive Outsider | 1      | 999996 | olive.outsider@portlandoregon.gov | Authenticated user |
    # | Mary Member    | 1      | 999997 | mary.member@portlandoregon.gov    | Authenticated user |
    # | Adam Admin     | 1      | 999998 | adam.admin@portlandoregon.gov     | Authenticated user |
    # | Sam Superadmin | 1      | 999999 | sam.superadmin@portlandoregon.gov | Administrator      |
