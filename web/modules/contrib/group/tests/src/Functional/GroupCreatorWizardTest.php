<?php

namespace Drupal\Tests\group\Functional;

/**
 * Tests the group creator wizard.
 *
 * @group group
 */
class GroupCreatorWizardTest extends GroupBrowserTestBase {

  /**
   * Tests that a group creator gets a membership using the wizard.
   */
  public function testCreatorMembershipWizard() {
    $this->drupalGet('/group/add/default');
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Create Default label and complete your membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Cancel');

    $edit = ['Title' => $this->randomString()];
    $this->submitForm($edit, $submit_button);

    $submit_button = 'Save group and membership';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonExists('Back');
  }

  /**
   * Tests that a group creator gets a membership without using the wizard.
   */
  public function testCreatorMembershipNoWizard() {
    /* @var \Drupal\group\Entity\GroupTypeInterface $group_type */
    $group_type = $this->entityTypeManager->getStorage('group_type')->load('default');
    $group_type->set('creator_wizard', FALSE);
    $group_type->save();

    $this->drupalGet('/group/add/default');
    $this->assertSession()->statusCodeEquals(200);

    $submit_button = 'Create Default label and become a member';
    $this->assertSession()->buttonExists($submit_button);
    $this->assertSession()->buttonNotExists('Cancel');
  }

  /**
   * Tests that a group form is not turned into a wizard.
   */
  public function testNoWizard() {
    $this->drupalGet('/group/add/other');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists('Create Other');
    $this->assertSession()->buttonNotExists('Cancel');
  }

}
