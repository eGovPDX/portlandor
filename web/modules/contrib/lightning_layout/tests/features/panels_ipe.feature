@lightning @lightning_layout @api @errors
Feature: Creating and editing landing pages visually

  @landing-page @1e244c89s
  Scenario: Panelizer is enabled for landing pages
    Given I am logged in as a user with the landing_page_creator role
    And landing_page content:
      | title  | path    |
      | Foobar | /foobar |
    When I visit "/foobar"
    Then I should see a "#panels-ipe-content" element
    And I should not see a ".field--name-uid" element
    And I should not see a ".field--name-created" element

  @landing-page @javascript @43f95224
  Scenario: One-off changes can be made to Landing Pages using the IPE out of the box.
    Given I am logged in as a user with the landing_page_creator role
    And landing_page content:
      | title  | path    |
      | Foobar | /foobar |
    When I visit "/foobar"
    And I place the "views_block:who_s_online-who_s_online_block" block from the "Lists (Views)" category
    And I save the layout
    And I visit "/foobar"
    Then I should see a "views_block:who_s_online-who_s_online_block" block

  @landing-page @javascript @c1cdf042
  Scenario: Quick-editing custom blocks in an IPE layout
    Given I am logged in as a user with the administrator role
    And landing_page content:
      | title  | path    |
      | Foobar | /foobar |
    And block_content entities:
      | type  | info               | body    | uuid                  |
      | basic | Here be dragons... | RAWWWR! | test--here-be-dragons |
    When I visit "/foobar"
    And I place the "block_content:test--here-be-dragons" block from the "Custom" category
    And I save the layout
    And I reload the page
    And I wait 5 seconds
    Then I should see a "block_content:test--here-be-dragons" block with a "quickedit" contextual link

  @landing-page @javascript @e787aec5
  Scenario: Quick-editing fields placed in an IPE layout
    Given I am logged in as a user with the administrator role
    And landing_page content:
      | title  | path    | body                                 |
      | Foobar | /zebras | How quickly deft jumping zebras vex. |
    When I visit "/zebras"
    And I place the "entity_field:node:body" block from the "Content" category
    And I save the layout
    And I reload the page
    And I wait 5 seconds
    Then I should see a "system_main_block" block with a "quickedit" contextual link

  @landing-page @javascript @ccabe17e
  Scenario: Changing layouts through the IPE
    Given I am logged in as a user with the landing_page_creator role
    And landing_page content:
      | title  | path    |
      | Foobar | /foobar |
    When I visit "/foobar"
    And I change the layout to "layout_threecol_25_50_25" from the "Columns: 3" category
    Then I should see "Region: first"
    And I should see "Region: second"
    And I should see "Region: third"
    When I change the layout to "layout_twocol" from the "Columns: 2" category
    Then I should see "Region: first"
    And I should see "Region: second"
    And I should not see "Region: third"
