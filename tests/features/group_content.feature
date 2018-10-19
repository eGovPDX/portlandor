@api @javascript @multidev @dev
Feature: Members can manage group content
  In order to manage content in my group
  As a group member
  I need to see and use group content links

  Scenario: See tab to add content
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/transportation"
    Then I should see "+ Add Content"

  Scenario: Visit group content
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/group/14/nodes"
    Then I should see "ADA parking permit"

  Scenario: See tab to add content
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/transportation"
    Then I should see "+ Add Media"

  Scenario: Visit group media
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/group/14/media"
    Then I should see "Transportation media"
