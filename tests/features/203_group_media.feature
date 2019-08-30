@api @javascript @dev @203
Feature: Members can manage group media
  In order to manage media in my group
  As a group member
  I need to be able to create and edit media entities

  Background:
    Given I am using a 1440x900 browser window

  # Assume Marty Member is a member of POWR
  Scenario: Add media
    Given I am logged in as user "Marty Member"
    When I visit "/powr"
    Then I wait for the page to be loaded
    And I should see "+ Add Media"

    When I click "+ Add Media"
    Then I wait for the page to be loaded
    And I should see "Add Video"

    When I click "Add Video"
    Then I wait for the page to be loaded
    And I should see "Create"
    And I should see "Name"
    And I should see "Video URL"

    When I fill in "Name" with "A test video"
    And I fill in "Video URL" with "https://www.youtube.com/watch?v=Deguep26G7M"
    And I press "Save"
    Then I wait for the page to be loaded
    And I should see "Video A test video has been created."

  Scenario: Delete media
    Given I am logged in as user "Marty Member"
    When I visit "/powr"
    Then I wait for the page to be loaded
    And I should see "Media" in the "tabs" region

    When I click "Media" in the "tabs" region
    Then I wait for the page to be loaded
    And I should see "Manage Portland Oregon Website Replacement Media"

    When I click "A test video"
    Then I wait for the page to be loaded
    And I should see "Delete" in the "tabs" region

    When I click "Delete" in the "tabs" region
    Then I wait for the page to be loaded
    And I should see "This action cannot be undone."

    When I press "Delete"
    Then I wait for the page to be loaded
    And I should see "The media A test video has been deleted."


