@api @javascript @multidev @dev
Feature: Edit a service feature
  In order to manage services
  As a group member
  I need to be able to edit a service

  Scenario: Access edit form
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/node/52/edit"
    Then I should see "Title"
    And I should see "Step title"

  Scenario: Submit edit form
    Given I am logged in as user "marty.member@portlandoregon.gov"
    And I am on "/node/52/edit"
    When I press "Save"
    Then I should see a ".alert.alert-success" element
    And I should not see a ".alert.alert-danger" element
