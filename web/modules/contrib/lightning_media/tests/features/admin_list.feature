@lightning @api @lightning_media
Feature: Media content list page

  Background:
    Given media items:
      | bundle    | name             | embed_code                                                  | status | field_media_in_library |
      | tweet     | I'm a tweet      | https://twitter.com/50NerdsofGrey/status/757319527151636480 | 1      | 1                      |
      | instagram | I'm an instagram | https://www.instagram.com/p/BaecNGYAYyP/                    | 1      | 1                      |

  @181ec740
  Scenario: Media filters and actions are present
    Given I am logged in as a user with the "access media overview" permission
    When I visit "/admin/content/media"
    Then I should see "Published status"
    And I should see a "Type" field
    And I should see a "Media name" field
    And I should see a "Language" field
    And I should see an "Action" field

  @bd2a222b
  Scenario: Media filters are functional
    Given I am logged in as a user with the "access media overview" permission
    When I visit "/admin/content/media"
    And I select "Tweet" from "Type"
    And I apply the exposed filters
    Then I should see "I'm a tweet"
    And I should not see "I'm an instagram"

  @c292f45d
  Scenario: Media actions are functional
    Given I am logged in as a user with the "access media overview, delete any media" permissions
    When I visit "/admin/content/media"
    And I should see "I'm a tweet"
    And I should see "I'm an instagram"
    And I select "Delete media" from "Action"
    And I check the box "media_bulk_form[0]"
    And I check the box "media_bulk_form[1]"
    And I press the "Apply to selected items" button
    And I press the "Delete" button
    Then I should see "Deleted 2 items."
    And I should not see "I'm a tweet"
    And I should not see "I'm an instagram"
