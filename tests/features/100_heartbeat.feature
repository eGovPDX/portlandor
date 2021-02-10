@api @javascript @multidev @dev @100
Feature: Basic site operation and navigation
  In order to use portland.gov
  As an anonymous or authenticated user
  I need to be able to navigate from the homepage

  Background:
    Given I am using a 1440x900 browser window

  Scenario: Visit the homepage as anonymous user
    When I visit "/"
    Then I wait for the page to be loaded
    And I should see "City of Portland, Oregon" in the "footer_fourth" region
    And I click the ".cloudy-header__toggle--menu" element
    And I wait 1 seconds
    Then I should see "Services and Resources" in the "main_menu" region

  Scenario: Visit the homepage as authenticated user
    Given I am logged in as user "oliver.outsider@portlandoregon.gov"
    When I visit "/"
    Then I wait for the page to be loaded
    And I should see "City of Portland, Oregon" in the "footer_fourth" region
    And I click the ".cloudy-header__toggle--menu" element
    And I wait 1 seconds
    Then I should see "Services and Resources" in the "main_menu" region

    # Given users:
    # | name           | status | uid    | mail                              | roles              |
    # | Olive Outsider | 1      | 999996 | olive.outsider@portlandoregon.gov | Authenticated user |
    # | Mary Member    | 1      | 999997 | mary.member@portlandoregon.gov    | Authenticated user |
    # | Adam Admin     | 1      | 999998 | adam.admin@portlandoregon.gov     | Authenticated user |
    # | Sam Superadmin | 1      | 999999 | sam.superadmin@portlandoregon.gov | Administrator      |
