Feature: Create group admin user
  In order to manage group administrators
  As a sitewide administrator
  I need to be able to create users and assign them as group admins

  Scenario: Assign users to group
    Given users: 
    | name        | mail                           | roles |
    | Adam Admin  | adam.admin@portlandoregon.gov  |       |
    | Mary Member | mary.member@portlandoregon.gov |       |
    And I am logged in as "superAdmin"
    When I visit "/group/15/members" # powr group
    Then I should see "Manage Technology Services Members"

    When I click the link "Add member"
    Then I should see "Create Project: Group membership" in the "content" region
    And I should see "Username"

    When I fill in the autocomplete "edit-entity-id-0-target-id" with "Adam Admin" and click "Adam Admin"
    And I press "Save"
    Then I should see "Manage Technology Services Members"
    And I should see "Adam Admin"
