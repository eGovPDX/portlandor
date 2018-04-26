<?php

namespace Drupal\Tests\lightning_layout\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group lightning
 * @group lightning_layout
 */
class ViewModeCustomizationTest extends BrowserTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_landing_page',
  ];

  public function testViewModeDescription() {
    $display = entity_get_display('node', 'landing_page', 'search_result');

    $display
      ->setStatus(TRUE)
      ->setThirdPartySetting('panelizer', 'enable', TRUE)
      ->setThirdPartySetting('panelizer', 'custom', TRUE)
      ->setThirdPartySetting('panelizer', 'allow', TRUE)
      ->save();

    $account = $this->drupalCreateUser(['create landing_page content']);
    $this->drupalLogin($account);
    $this->drupalGet('/node/add/landing_page');

    $assert = $this->assertSession();
    $assert->fieldExists('Full content');
    $assert->fieldExists('Search result highlighting input');

    $display->setStatus(FALSE)->save();
    $this->getSession()->reload();
    $assert->fieldExists('Full content');
    $assert->fieldNotExists('Search result highlighting input');
  }

}
