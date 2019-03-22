@api @multidev @dev
Feature: Members can manage group content
  In order to manage content in my group
  As a group member
  I need to be able to create and edit group content nodes

  Scenario: Add service content
    Given I am logged in as user "Marty Member"
    When I visit "/test"
    Then I should see "+ Add Content"

    When I click "+ Add Content"
    And I should see "Group node (Event)"
    And I should see "Group node (News)"
    Then I should see "Group node (Page)"
    And I should see "Group node (Notification)"
    And I should see "Group node (Service)"

    When I click "Group node (Service)"
    And I should see "Title"
    And I should see "Step title"
    And I should see "Step instruction"
    And I should see "Related content"
    And I should see "Legacy path"

    When I fill in "edit-title-0-value" with "Test service"
    And I select "Online" from "edit-field-service-mode-0-subform-field-service-modes"
    And I fill in "edit-revision-log-0-value" with:
      """
      Test revision message
      """
    And I press "Save"
    Then I should see "Service Test service has been created."

  Scenario: Edit and delete service
    Given I am logged in as user "Marty Member"
    When I visit "/test/services/test-service"
    And I click "Edit" in the "tabs" region
    Then I should see "Title"

    When I click "Delete" in the "tabs" region
    Then I should see "This action cannot be undone."

    When I press "Delete"
    Then I should see "The Service Test service has been deleted."
