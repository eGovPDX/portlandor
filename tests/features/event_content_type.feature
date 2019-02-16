@api @javascript @multidev @dev
Feature: Group members can create events
  In order to manage events in my group
  As a group member
  I need to be able to use the Add Event form

  Scenario: Add a user to a group
    # Add marty.member to BTS group before attempting to create group content
    Given I am logged in as user "superAdmin"
    When I visit "/group/15/members"
    Then I should see "Manage Technology Services Members" in the "content" region
    # And I shoud see the link "+ Add member" in the "content" region

    When I click "+ Add member"
    Then I should see "Create Bureau/office: Group membership" in the "content" region
    And I should see "Username"
    
    When I fill in "edit-entity-id-0-target-id" with "Marty Member (63)"
    And I press "Save"
    Then I should see "Manage Technology Services Members"
    And I should see "Marty Member"


  # Scenario: Add an event to a group
  #   Given I am logged in as user ""
  #   When I visit "/powr"
  #   Then I should see "+ Add Content"

  #   When I click "+ Add Content"
  #   Then I should see "Group node (Event)"

  #   When I click "Group node (Event)"
  #   Then I should see "Create Project: Group node (Event)"
  #   And I should see "Start date"
  #   And I should see "Start time"
  #   And I should see "End date"
  #   And I should see "End time"

  #   Given I check the box "All day"
  #   Then I should not see "Start time"
  #   And I should not see "End time"

    # When I fill in "edit-title-0-value" with "Test event"
    # And I fill in "edit-field-summary-0-value" with "This is the summary field"

    # And I fill in wysiwyg on field "edit-field-body-content-0-value" with "This is the body field"

    # And I fill in "edit-field-legacy-path-0-value" with "/legacy/path/1234"
    # And I fill in "edit-revision-log-0-value" with "Test revision message"
    # And I press "Save"
    # Then I should see "Page Test page has been created"
    # And I should see "Information"
    # And I should see "This is the summary field"
    # And I should see "This is the body field"

