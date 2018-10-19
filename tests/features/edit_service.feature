@api @javascript @multidev @dev
Feature: Add a service feature
  In order to edit services
  As a group member
  I need to be able to edit a service

  Scenario: Access edit form
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/node/52/edit"
    Then I should see "Title"
    And I should see "Step title"
    And I should see "Add button"
    And I should see "Save"

  Scenario: Submit edit form
    Given I am logged in as user "marty.member@portlandoregon.gov"
    And I am on "/node/52/edit"
    When I press "Save"
    Then I should not see "warning"
    And I should not see "error"
