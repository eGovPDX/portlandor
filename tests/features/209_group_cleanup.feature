@javascript
Feature: Remove group and delete test user
  In order to manage groups and users
  As a sitewide administrator
  I need to be able to create a group and assign users to it

  @api @multidev @dev
  Scenario: Create group and assign user

    # Given I am logged in as user "marty.member@portlandoregon.gov"
    # And I am viewing a group of type "bureau_office" with the title "A test group 2"
    # Then I am a member of the current group
    # And I load the group with title "A test group 2"

    Given I am logged in as user "superAdmin"
    When I visit "/test"
    Then I should see "A test group"

    When I click "Delete"
    Then I should see "Are you sure you want to delete the group A test group?"

    When I press "Delete"
    Then I should see "bureau_office A test group has been deleted"


