Feature: Create group and assign users
  In order to manage groups and users
  As a sitewide administrator
  I need to be able to create a group and assign users to it

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
    And I press button  "Create Bureau/office"

    Then I should see "This is a test summary for the Test group"

