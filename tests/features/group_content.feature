Feature: Members can manage group content
  In order to manage content in my group
  As a group member
  I need to see and use group content links

  @api @multidev
  Scenario: See tab to add content
    Given I am logged in as "marty.member@portlandoregon.gov"
    When I visit "/transportation"
    Then I should see "+ Add Content"

  @api @multidev
  Scenario: Visit group content
    Given I am logged in as "marty.member@portlandoregon.gov"
    When I visit "/group/14/nodes"
    Then I should see "ADA parking permit"

Feature: Members can manage group media
  In order to manage media for my group
  As a group member
  I need to see group media

  @api @multidev
  Scenario: See tab to add content
    Given I am logged in as "marty.member@portlandoregon.gov"
    When I visit "/transportation"
    Then I should see "+ Add Media"

  @api @multidev
  Scenario: Visit group media
    Given I am logged in as "marty.member@portlandoregon.gov"
    When I visit "/group/14/media"
    Then I should see "Transportation media"
