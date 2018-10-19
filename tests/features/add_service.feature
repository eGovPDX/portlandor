@api @javascript @multidev @dev
Feature: Add a service feature
  In order to edit services
  As a group member
  I need to be able to add a service

  Scenario: Access content creation page
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/group/14/node/create"
    Then I should see "Service"
    And I should see "Event"
    And I should see "Guide"
    And I should see "News"
    And I should see "Information"

  Scenario: Access service creation page and have stuff to do
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/group/14/content/create/group_node%3Acity_service"
    Then I should see "Title"
    And I should see "Step title"
    And I should see "Add button"
    And I should see "Save"
