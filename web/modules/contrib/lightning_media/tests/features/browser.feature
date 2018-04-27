@lightning @lightning_media @api @javascript @errors
Feature: An entity browser for media assets

  Background:
    Given I am logged in as a user with the media_creator role
    And media items:
      | bundle | name          | embed_code                                               | status | field_media_in_library |
      | tweet  | Code Wisdom 1 | https://twitter.com/CodeWisdom/status/707945860936691714 | 1      | 1                      |
      | tweet  | Code Wisdom 2 | https://twitter.com/CodeWisdom/status/826500049760821248 | 1      | 1                      |
      | tweet  | Code Wisdom 3 | https://twitter.com/CodeWisdom/status/826460810121773057 | 1      | 1                      |

  @twitter @fe9a2c68
  Scenario: Opening the media browser on a pre-existing node
    Given page content:
      | type | title | path   |
      | page | Blorf | /blorf |
    When I visit "/blorf"
    And I visit the edit form
    And I open the media browser
    And I select item 1 in the media browser
    And I complete the media browser selection
    And I wait 5 seconds
    And I press "Save"
    And I visit the edit form
    And I wait 10 seconds
    And I open the media browser
    And I wait for AJAX to finish
    Then I should see a "form.entity-browser-form" element

  @twitter @ee4d5a41
  Scenario: Testing cardinality enforcement in the media browser
    When I visit "/node/add/page"
    And I open the media browser
    And I wait 5 seconds
    # There was a bug where AJAX requests would completely break the selection
    # behavior. So let's make an otherwise pointless AJAX request here to guard
    # against regressions...
    And I enter "Pastafazoul!" for "Keywords"
    And I apply the exposed filters
    And I clear "Keywords"
    And I apply the exposed filters
    And I select item 1 in the media browser
    And I select item 2 in the media browser
    Then I should see a "[data-selectable].selected" element
    # No choices are ever disabled in a single-cardinality entity browser.
    And I should see 0 "[data-selectable].disabled" elements

  @81cfbefc
  Scenario: Bundle filter is present when no contextual filter is given.
    When I visit "/node/add/page"
    And I open the media browser
    Then I should see a "Type" field

  @6b941640
  Scenario: Entity browser filters work
    When I visit "/node/add/page"
    And I open the media browser
    And I wait 5 seconds
    And I enter "Code Wisdom 1" for "Keywords"
    And I apply the exposed filters
    Then I should see 1 item in the entity browser
