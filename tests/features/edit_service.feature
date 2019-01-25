@api @javascript @multidev @dev
Feature: Edit a service feature
  In order to manage services
  As a group member
  I need to be able to edit a service

  Background: On ADA Parking Permit edit form
    Given I am logged in as user "marty.member@portlandoregon.gov"
    And I am on "/node/52/edit"

  Scenario: Submit edit form
    Then I should see "Title"

  Scenario: Archive a service
    When I select "Archived" from "edit-moderation-state-0-state"
    And I fill in "edit-revision-log-0-value" with:
      """
      Test revision message
      """
    And I press "Save"
    Then I should see "Archived"
    And I should see "has been updated."