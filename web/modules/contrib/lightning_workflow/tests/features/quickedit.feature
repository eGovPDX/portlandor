@lightning @api @lightning_workflow @javascript
Feature: Integration of workflows with Quick Edit

  Background:
    Given page content:
      | type | title  | path    | moderation_state |
      | page | Foobar | /foobar | draft            |

  @f2beeeda
  Scenario: Quick Edit should be available for unpublished content
    Given I am logged in as a user with the "access in-place editing, access contextual links, use editorial transition create_new_draft, view any unpublished content, edit any page content" permissions
    When I visit "/foobar"
    Then Quick Edit should be enabled

  @b62c6213
  Scenario: Quick Edit should be disabled for published content
    Given I am logged in as a user with the "use editorial transition publish, view own unpublished content, access in-place editing, access contextual links, view any unpublished content, edit any page content" permissions
    When I visit "/foobar"
    And I visit the edit form
    And I select "published" from "moderation_state[0][state]"
    And I press "Save"
    Then Quick Edit should be disabled

  @fb59aafc
  Scenario: Quick Edit should be enabled on forward revisions
    # The content roles do not have the ability to transition content from
    # Published to Draft states.
    Given I am logged in as a user with the administrator role
    When I visit "/foobar"
    And I visit the edit form
    And I select "published" from "moderation_state[0][state]"
    And I press "Save"
    And I visit the edit form
    And I select "draft" from "moderation_state[0][state]"
    And I press "Save"
    And I click "Latest version"
    Then Quick Edit should be enabled
