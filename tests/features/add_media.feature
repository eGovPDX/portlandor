@api @javascript @multidev @dev
Feature: Add media feature
  In order to manage media
  As a group member
  I need to be able to add media

  Scenario: Access audio creation page and create audio without error
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/group/14/content/create/group_media%3Aaudio"
    And I fill in "Name" with "Test audio"
    And I fill in "Audio Url" with "https://www.youtube.com/watch?v=9bZkp7q19f0"
    And I press "Save"
    Then I should see an ".alert.alert-success" element
    And I should not see a ".alert.alert-danger" element
    And I should see "Test audio"

  Scenario: Access video creation page and create video without error
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/group/14/content/create/group_media%3Avideo"
    And I fill in "Name" with "Test video"
    And I fill in "Video Url" with "https://www.youtube.com/watch?v=9bZkp7q19f0"
    And I press "Save"
    Then I should see an ".alert.alert-success" element
    And I should not see a ".alert.alert-danger" element
    And I should see "Test video"
