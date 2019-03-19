@javascript
Feature: Create group and assign users
  In order to manage groups and users
  As a sitewide administrator
  I need to be able to create a group and assign users to it

  @api @multidev @dev
  Scenario: Create group
    Given I am logged in as "superAdmin"
    When I visit "/group/add/bureau_office"
    Then I should see "Add Bureau/office"

    When I fill in "edit-label-0-value" with "A test group"
    And I fill in "edit-field-official-organization-name-0-value" with "A very nice test group"
    And I fill in "edit-field-shortname-or-acronym-0-value" with "test"
    And I fill in "edit-field-address-0-address-address-line1" with "123 Fake St"
    And I fill in "edit-field-address-0-address-locality" with "Portland"
    And I select "OR" from "edit-field-address-0-address-administrative-area"
    And I fill in "edit-field-address-0-address-postal-code" with "97201"
    And I fill in "edit-field-summary-0-value" with "This is a test summary for the Test group"
    And I press "Create Bureau/office"

    Then I should see "This is a test summary for the Test group"

  @api @multidev @dev
  Scenario: Assign users to group
    Given users: 
    | name        | mail                           | roles              |
    | Adam Admin  | adam.admin@portlandoregon.gov  | Administrator      |
    | Mary Member | mary.member@portlandoregon.gov | Authenticated user |
    And I am logged in as "superAdmin"
    When I visit "/test"
    And I click "Members"
    Then I should see "Add member"

    When I click "Add member"
    Then I should see "Create Bureau/office: Group membership" in the "content" region
    And I should see "Username"

    When I fill in the autocomplete "edit-entity-id-0-target-id" with "Mary Member" and click "Mary Member"
    And I press "Save"
    Then I should see "Manage A test group Members"
    And I should see "Mary Member"
