@lightning @core @search @api @errors
Feature: Site search

  @page @e4c5b23b
  Scenario: Unpublished content does not appear in search results
    Given I am an anonymous user
    And page content:
      | title    | status | body                                                                   |
      | Zombie 1 | 0      | Zombie ipsum reversus ab viral inferno, nam rick grimes malum cerebro. |
      | Zombie 2 | 0      | De carne lumbering animata corpora quaeritis.                          |
      | Zombie 3 | 1      | Summus brains sit, morbo vel maleficia?                                |
    When I visit "/search"
    And I enter "zombie" for "Keywords"
    And I press "Search"
    Then the response status code should be 200
    And I should not see the link "Zombie 1"
    And I should not see the link "Zombie 2"
    And I should see the link "Zombie 3"
