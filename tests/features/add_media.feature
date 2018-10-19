@api @javascript @multidev @dev
Feature: Add media feature
  In order to manage media
  As a group member
  I need to be able to add an image

  Scenario: Access content creation page
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/group/14/media/create"
    Then I should see "Audio"
    And I should see "Image"
    And I should see "Document"
    And I should see "Video"

  Scenario: Access image creation page and create an image without an error
    Given I am logged in as user "marty.member@portlandoregon.gov"
    When I visit "/group/14/content/create/group_media%3Aimage"
    And I fill in "edit-name-0-value" with "City seal"
    And I attach the file "city-seal.png" to "edit-image-0-upload"
    And I wait for AJAX to finish
    And I fill in "image[0][alt]" with "City seal"
    And I press "Save"
    Then I should see the heading "City seal"
    And I should see a "success_message_selector" element
    And I should not see a "error_message_selector" element
