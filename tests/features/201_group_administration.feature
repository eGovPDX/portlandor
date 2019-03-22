@api @multidev @dev
Feature: Assign members to group
  In order to manage group members
  As a group administrator
  I need to be able to assign users to that group

  Scenario: Assign member to group as group admin

    Given I am logged in as user "Ally Admin"
    When I visit "/test"
    And I click "Members"
    Then I should see "Add member"

    When I click "Add member"
    Then I should see "Create Bureau/office: Group membership"
    And I should see "Username"

    When I fill in "edit-entity-id-0-target-id" with "Marty Member (63)"
    And I press "Save"
    Then I should see "Manage A test group Members"
    And I should see "Marty Member"


