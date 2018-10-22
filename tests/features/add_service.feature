@api @javascript @multidev @dev
Feature: Add a service feature
  In order to manage services
  As a group member
  I need to be able to add a service

  Scenario: Access service creation page and have stuff to do
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/group/14/content/create/group_node%3Acity_service"
    Then I should see "Title"
    And I should see "Step title"
    And I should see "Step instruction"
    And I should see "Related content"
    And I should see "Legacy path"

    When I press "Add Step"
    And I wait for AJAX to finish
    Then I should see "Step"

    When I fill in "Title" with "Test service"
    And I press "Save"
    Then I should not see an ".alert.alert-success" element

    When I select "Online" from "edit-field-service-mode-0-subform-field-service-modes"
    And I press "Save"
    Then I should see an ".alert.alert-success" element
