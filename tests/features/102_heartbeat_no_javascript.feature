@javascript @api @multidev @dev @102
Feature: Basic site operation and navigation
  In order to use portland.gov
  As an anonymous or authenticated user
  I need to be able to navigate from the homepage

  Scenario: Visit a taxonomy term page
    Given I am logged in as user "oliver.outsider@portlandoregon.gov"
    When I visit "/residents-of-portland"
    Then I should see "Resident"
    And I should see "services found"
    And I should see "Bureaus, offices, and programs"
    # Verify fix for POWR-889. Use "Given I am on" in order to allow non-200 response code
    Given I am on "/taxonomy/term/100000"
    Then I should see "Page not found"

    # Given users:
    # | name           | status | uid    | mail                              | roles              |
    # | Olive Outsider | 1      | 999996 | olive.outsider@portlandoregon.gov | Authenticated user |
    # | Mary Member    | 1      | 999997 | mary.member@portlandoregon.gov    | Authenticated user |
    # | Adam Admin     | 1      | 999998 | adam.admin@portlandoregon.gov     | Authenticated user |
    # | Sam Superadmin | 1      | 999999 | sam.superadmin@portlandoregon.gov | Administrator      |
