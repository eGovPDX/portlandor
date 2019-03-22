@api @multidev @dev
Feature: Members can manage group media
  In order to manage media in my group
  As a group member
  I need to be able to create and edit media entities

  Scenario: Add media
    Given I am logged in as user "Marty Member"
    When I visit "/test"
    Then I should see "+ Add Media"

    When I click "+ Add Media"
    Then I should see "Group media (Audio)"
    And I should see "Group media (Image)"
    And I should see "Group media (Video)"
    And I should see "Group media (Document)"

    When I click "Group media (Video)"
    Then I should see "Create Bureau/office: Group media (Video)"

    When I fill in "Name" with "A test video"
    And I fill in "Video URL" with "https://www.youtube.com/watch?v=Deguep26G7M"
    And I press "Save"
    Then I should see "Video A test video has been created."

  Scenario: Delete media
    Given I am logged in as user "Marty Member"
    When I visit "/test"
    Then I should see "Media" in the "tabs" region

    When I click "Media" in the "tabs" region
    Then I should see "Manage A test group Media"

    When I click "A test video"
    Then I should see "Delete" in the "tabs" region

    When I click "Delete" in the "tabs" region
    Then I should see "This action cannot be undone."

    When I press "Delete"
    Then I should see "The media A test video has been deleted."


