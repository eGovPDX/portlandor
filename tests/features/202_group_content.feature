@api @javascript @dev @202
Feature: Members can manage group content
  In order to manage content in my group
  As a group member
  I need to be able to create and edit group content nodes

  Background:
    Given I am using a 1440x900 browser window

  # Assume Marty Member is a member of POWR
  Scenario: Add page content
    Given I am logged in as user "Marty Member"
    When I visit "/powr"
    Then I wait for the page to be loaded
    And I should see "+ Add Content"

    When I click "+ Add Content"
    Then I wait for the page to be loaded
    And  I should see "Add Page"

    When I click "Add Page"
    And I should see "Title"
    And I should see "Page type"
    And I should see "Summary"
    And I should see "Body content"
    And I should see "Legacy path"

    When I fill in "edit-title-0-value" with "Test page"
    And I fill in "edit-field-summary-0-value" with "Summary for the test page"
    And I fill in wysiwyg on field "edit-field-body-content-0-value" with "Body content for the test page"
    And I fill in "edit-revision-log-0-value" with:
      """
      Test revision message
      """
    And I press "Save"
    Then I wait for the page to be loaded
    And I should see "Page Test page has been created."

  Scenario: Edit and delete service
    Given I am logged in as user "Marty Member"
    When I visit "/powr/test-page"
    Then I wait for the page to be loaded
    And I click "Edit" in the "tabs" region
    Then I wait for the page to be loaded
    And I should see "Title"

    When I click "Delete" in the "tabs" region
    Then I wait for the page to be loaded
    And I should see "This action cannot be undone."

    When I press "Delete"
    Then I wait for the page to be loaded
    And I should see "has been deleted."
