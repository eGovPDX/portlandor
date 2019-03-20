@javascript
Feature: Members can manage group content
  In order to manage content in my group
  As a group member
  I need to see and use group content links

  @api @multidev @dev
  Scenario: Access tab to add content
    Given users: 
    | name         | mail                            | roles              |
    | Marty Member | marty.member@portlandoregon.gov | Authenticated user |
    And I am logged in as user "Marty Member" and a member of group "A test group"
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

    # do we need to test actual page creation, or just ability to reach add/edit page?

  Scenario: Visit group content
    Given I am logged in as user "Marty Member" and a member of group "A test group"
    When I visit "/test"
    Then I should see "Content"

    When I click "Content"
    Then I should see "Manage A test group Content"

  Scenario: See tab to add media
    Given I am logged in as user "Marty Member" and a member of group "A test group"
    When I visit "/test"
    Then I should see "+ Add Media"

    When I click "+ Add Media"
    Then I should see "Group media (Audio)"
    And I should see "Group media (Image)"
    And I should see "Group media (Video)"
    And I should see "Group media (Document)"

  Scenario: Visit group media
    Given I am logged in as user "Marty Member" and a member of group "A test group"
    When I visit "/test"
    Then I should see "Media"

    When I click "Media"
    Then I should see "Manage A test group Media"
