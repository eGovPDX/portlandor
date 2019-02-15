@api @javascript @multidev @dev
Feature: Users can create content of type Event
  In order to manage content on the site
  As a sitewide administrator
  I need to be set values in all Event fields

  Scenario: Add and delete a page of type Event
    Given I am logged in as user ""
    When I visit "/powr"
    Then I should see "+ Add Content"

    When I click "+ Add Content"
    Then I should see "Group node (Event)"

    When I click "Group node (Event)"
    Then I should see "Create Project: Group node (Event)"
    And I should see "Start date"
    And I should see "Start time"
    And I should see "End date"
    And I should see "End time"

    Given I check the box "All day"
    Then I should not see "Start time"
    And I should not see "End time"

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

