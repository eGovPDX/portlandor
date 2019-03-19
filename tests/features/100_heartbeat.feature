@javascript
Feature: Site is running
  In order to test that the site is up
  As an anonymous user
  I need to be able to see the homepage

  @api @multidev @dev
  Scenario: Visit the homepage
    When I visit "/"
    Then I should see "Portland, Oregon"
    And I should see "Services"
