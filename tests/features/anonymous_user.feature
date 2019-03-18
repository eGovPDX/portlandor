@javascript
Feature: Demo feature
  In order to test Drupal
  As an anonymous user
  I need to be able to see the homepage

  Background: Visit as an anonymous user
    Given I am an anonymous user

  @multidev @dev
  Scenario: Visit the homepage
    When I visit "/"
    # Then print last response
    Then I should see "Portland, Oregon"
