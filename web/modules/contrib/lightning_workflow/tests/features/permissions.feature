@lightning @api @workflow
Feature: Editorial permissions

  Background:
    Given I am logged in as a user with the administrator role
    And page content:
      | title     | moderation_state | path     |
      | Version 1 | draft            | /slipsum |

  @5579cfff
  Scenario: Viewing unpublished content as a reviewer
    When I am logged in as a user with the page_reviewer role
    And I visit "/slipsum"
    Then the response status code should be 200
    And I should see "Version 1"

  @1e384da8
  Scenario: Viewing the latest unpublished version of content as a reviewer
    When I visit "/slipsum"
    And I visit the edit form
    And I enter "Version 2" for "Title"
    And I select "published" from "moderation_state[0][state]"
    And I press "Save"
    And I visit the edit form
    And I enter "Version 3" for "Title"
    And I select "draft" from "moderation_state[0][state]"
    And I press "Save"
    And I am logged in as a user with the page_reviewer role
    And I visit "/slipsum"
    Then the response status code should be 200
    And I should see the link "Latest version"
