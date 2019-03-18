@api @javascript @multidev @dev
Feature: Members can manage group content
  In order to manage content in my group
  As a group member
  I need to see and use group content links

  Background: Login as Marty Member
    Given I am logged in as user "marty.member@portlandoregon.gov"

  Scenario: Access tab to add content
    When I visit "/transportation"
    Then I should see "+ Add Content"

    When I click "+ Add Content"
    Then I should see "Service"
    # And I should see "Event"
    # And I should see "Guide"
    # And I should see "News"
    # And I should see "Information"

  Scenario: Visit group content
    When I visit "/group/14/nodes"
    Then I should see "Manage Transportation Content"

  Scenario: See tab to add content
    When I visit "/transportation"
    Then I should see "+ Add Media"

    When I click "+ Add Media"
    Then I should see "Audio"
    And I should see "Image"
    And I should see "Document"
    And I should see "Video"

  Scenario: Visit group media
    When I visit "/group/14/media"
    Then I should see "Transportation"
