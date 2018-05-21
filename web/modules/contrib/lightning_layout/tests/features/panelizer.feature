@lightning @lightning_layout @api @errors
Feature: Panelizer

  @landing-page @javascript @29bc5778
  Scenario: Editing layouts does not affect other layouts if the user has not saved the edited layout as default
    Given I am logged in as a user with the administrator role
    And landing_page content:
      | title   | path     |
      | Layout1 | /layout1 |
      | Layout2 | /layout2 |
    When I visit "/layout1"
    And I place the "views_block:who_s_online-who_s_online_block" block from the "Lists (Views)" category
    # And visit the second landing page without saving the layout changes to the first
    And I visit "/layout2"
    # I should not see the block placed by the first landing page
    Then I should not see a "views_block:who_s_online-who_s_online_block" block
