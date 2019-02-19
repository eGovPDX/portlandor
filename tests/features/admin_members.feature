Feature: Administer group membership

  In order to manage my group
  As a group admin
  I need to be able to edit group membership

  Background: Group admin on group page
    Given I am logged in as user "ally.admin@portlandoregon.gov"
    And I am on "/transportation"

  Scenario: Access member creation
    When I click "Members"
    Then I should see "Transportation members"

    When I press "Add member"
    Then I should see "Username"


  Scenario: Edit member creation
    When I click "Members"
    Then I should see "Marty Member"
    And I should see "Edit"
    And I should see "Remove"

    # Very hard to press the right edit or remove link so I'll navigate directly
    #  to Marty Member's membership page
    When I go to "/group/14/content/61/edit"
    Then I should see "URL alias"
    And I should see "Roles"

    When I press "Save"
    # No message to look for
    Then I should be on "/group/14/members"
