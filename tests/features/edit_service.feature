@api @javascript @multidev @dev
Feature: Edit a service feature
  In order to manage services
  As a group member
  I need to be able to edit a service

  Background: On ADA Parking Permit edit form
    Given I am logged in as user "marty.member@portlandoregon.gov"
    And I am on "/node/52/edit"

  Scenario: Access edit form
    Then I should see "Title"
    And I should see "Step title"

  Scenario: Submit edit form
    When I press "Save"
    Then I should see a ".alert.alert-success" element
    And I should not see a ".alert.alert-danger" element
