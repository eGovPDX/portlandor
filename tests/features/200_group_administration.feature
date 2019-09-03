@api @javascript @dev @200
Feature: Create group
  In order to manage groups
  As a sitewide administrator
  I need to be able to create a group and assign users to it

  Background:
    Given I am using a 1440x900 browser window

  # This test feature must create a uniquely named group and delete it afterwards
  Scenario: Create group

    Given I am logged in as user "superAdmin"
    When I visit "/group/add/bureau_office"
    Then I wait for the page to be loaded
    And I should see "Add Bureau/office"

    When I fill in "edit-label-0-value" with "Behat test group"
    And I fill in "edit-field-official-organization-name-0-value" with "Official name of Behat test group"
    And I fill in "edit-field-shortname-or-acronym-0-value" with "behat-test"
    And I fill in "edit-field-address-0-address-address-line1" with "123 Fake St"
    And I fill in "edit-field-address-0-address-locality" with "Portland"
    And I select "OR" from "edit-field-address-0-address-administrative-area"
    And I fill in "edit-field-address-0-address-postal-code" with "97201"
    And I fill in "edit-field-summary-0-value" with "This is a test summary for the Behat Test group"
    And I press "Create Bureau/office"

    Then I should see "This is a test summary for the Behat Test group"

  Scenario: Assign admin to group

    Given I am logged in as user "superAdmin"
    When I visit "/behat-test"
    Then I wait for the page to be loaded
    And I click "Members"
    Then I should see "Add member"

    When I click "Add member"
    Then I wait for the page to be loaded
    And I should see "Add Bureau/office: Group membership"
    And I should see "Username"

    When I fill in "edit-entity-id-0-target-id" with "Ally Admin (62)"
    And I check the box "Admin"
    And I press "Save"
    Then I wait for the page to be loaded

    # If the following step fails, it is likely becasue the user is already a member
    When I visit "/behat-test"
    Then I wait for the page to be loaded
    And I click "Members"
    Then I should see "Manage"
    And I should see "Ally Admin"

  Scenario: Assign member to group

    Given I am logged in as user "Ally Admin"
    When I visit "/behat-test"
    Then I wait for the page to be loaded
    And I click "Members" in the "tabs" region
    Then I should see "Add member"

    When I click "Add member"
    Then I wait for the page to be loaded
    And I should see "Add Bureau/office: Group membership"
    And I should see "Username"

    When I fill in "edit-entity-id-0-target-id" with "Oliver Outsider (64)"
    And I press "Save"
    # If the following step fails, it is likely becasue the user is already a member    Then I should see "Manage A test group Members"
    Then I wait for the page to be loaded
    And I should see "Oliver Outsider"

  Scenario: Delete group

    Given I am logged in as user "superAdmin"
    When I visit "/behat-test"
    Then I wait for the page to be loaded
    And I click "Delete" in the "tabs" region
    And I should see "Are you sure"
    And I press "Delete"
    Then I wait for the page to be loaded
    And I should see "has been deleted"
