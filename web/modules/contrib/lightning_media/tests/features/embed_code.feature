@lightning @lightning_media @api
Feature: Media assets based on embed codes

  @video @javascript @c74eadd0 @with-module:test_c74eadd0
  Scenario: Clearing an image field on a media item
    Given I am logged in as a user with the "create media, update media" permission
    When I visit "/media/add/video"
    And I enter "Foobaz" for "Name"
    And I enter "https://www.youtube.com/watch?v=z9qY4VUZzcY" for "Video URL"
    And I wait for AJAX to finish
    And I attach the file "test.jpg" to "Image"
    And I wait for AJAX to finish
    And I press "Save"
    And I click "Edit"
    And I press "field_image_0_remove_button"
    And I wait for AJAX to finish
    # Ensure that the widget has actually been cleared. This test was written
    # because the AJAX operation would fail due to a 500 error at the server,
    # which would prevent the widget from being cleared.
    Then I should not see a "field_image_0_remove_button" element
