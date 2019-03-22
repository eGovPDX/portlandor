@javascript @api @multidev @dev
Feature: Members can manage group content
  In order to manage content in my group
  As a group member
  I need to be able to create and edit group content nodes

  Scenario: Access add content form
    Given I am logged in as user "Marty Member"
    When I visit "/test"
    Then I should see "+ Add Content"

    When I click "+ Add Content"
    And I should see "Group node (Event)"
    And I should see "Group node (News)"
    Then I should see "Group node (Page)"
    And I should see "Group node (Notification)"
    And I should see "Group node (Service)"

    When I click "Group node (Page)"
    Then I should see "Create Bureau/office: Group node (Page)"
    And I should see "Title"

    # do we need to test actual page creation, or just ability to reach add/edit page?

  Scenario: View group content
    Given I am logged in as user "Marty Member"
    When I visit "/test"
    Then I should see "Content" in the "page_tabs" region

    When I click "Content" in the "page_tabs" region
    Then I should see "results found"
