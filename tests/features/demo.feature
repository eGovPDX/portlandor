@javascript @api
Feature: Demo feature
  In order to test Drupal
  As an anonymous user
  I need to be able to see the homepage

  Scenario: Visit the homepage
    Given I am an anonymous user
    When I visit "/"
    # Then print last response
    Then I should see "Portland Oregon"
