@api @javascript @multidev @dev @add_media
Feature: Add media feature
  In order to manage media
  As a group member
  I need to be able to add media

  Background: Login as Marty Member
    Given I am logged in as user "marty.member@portlandoregon.gov"

  Scenario: Access audio creation page and create audio without error
    When I visit "/group/14/content/create/group_media%3Aaudio"
    And I fill in "Name" with "Test audio"
    And I fill in "Audio Url" with "https://www.youtube.com/watch?v=9bZkp7q19f0"
    And I press "Save"
    And I should see "Audio Test audio has been created."

  Scenario: Access video creation page and create video without error
    When I visit "/group/14/content/create/group_media%3Avideo"
    And I fill in "Name" with "Test video"
    And I fill in "Video URL" with "https://www.youtube.com/watch?v=9bZkp7q19f0"
    And I press "Save"
    And I should see "Video Test video has been created."
