@api @multidev @dev
Feature: Create group
  In order to manage groups
  As a sitewide administrator
  I need to be able to create a group and assign users to it

  Scenario: Create group

    # Given I am logged in as user "marty.member@portlandoregon.gov"
    # And I am viewing a group of type "bureau_office" with the title "A test group 2"
    # Then I am a member of the current group
    # And I load the group with title "A test group 2"

    Given I am logged in as user "superAdmin"
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

  Scenario: Assign admin to group

    Given I am logged in as user "superAdmin"
    When I visit "/test"
    And I click "Members"
    Then I should see "Add member"

    When I click "Add member"
    Then I should see "Create Bureau/office: Group membership"
    And I should see "Username"

    When I fill in "edit-entity-id-0-target-id" with "Ally Admin (62)"
    And I check the box "Admin"
    And I press "Save"
    # If the following step fails, it is likely becasue the user is already a member
    Then I should see "Manage <em class="placeholder">A test group</em> Members"
    And I should see "Ally Admin"

  Scenario: Assign member to group

    Given I am logged in as user "Ally Admin"
    When I visit "/test"
    And I click "Members"
    Then I should see "Add member"

    When I click "Add member"
    Then I should see "Create Bureau/office: Group membership"
    And I should see "Username"

    When I fill in "edit-entity-id-0-target-id" with "Marty Member (63)"
    And I press "Save"
    # If the following step fails, it is likely becasue the user is already a member    Then I should see "Manage A test group Members"
    And I should see "Marty Member"
