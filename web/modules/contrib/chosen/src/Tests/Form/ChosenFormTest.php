<?php

namespace Drupal\chosen\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Chosen form API test.
 *
 * @group Chosen
 */
class ChosenFormTest extends WebTestBase {

  public static $modules = array('chosen', 'chosen_test');

  /**
   * Test the form page
   */
  public function testFormPage() {
    $this->drupalGet('chosen-test');
    $this->dumpHeaders = TRUE;
    $this->assertText('Select');
    $this->assertTrue($this->xpath('//select[@id=:id and contains(@class, :class)]', array(':id' => 'edit-select', ':class' => 'chosen-enable')), 'The select has chosen enable class.');
  }

}
