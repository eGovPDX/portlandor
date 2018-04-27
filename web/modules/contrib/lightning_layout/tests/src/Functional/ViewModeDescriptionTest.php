<?php

namespace Drupal\Tests\lightning_layout\Functional;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Tests\BrowserTestBase;

/**
 * @group lightning
 * @group lightning_layout
 */
class ViewModeDescriptionTest extends BrowserTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_landing_page',
  ];

  public function testViewModeDescription() {
    $view_mode = EntityViewMode::load('node.full');
    $this->assertInstanceOf(EntityViewMode::class, $view_mode);
    $this->assertFalse($view_mode->isNew());

    $description = $this->getRandomGenerator()->sentences(4);
    $view_mode->setThirdPartySetting('lightning_core', 'description', $description);
    $view_mode->save();

    $account = $this->drupalCreateUser(['create landing_page content']);
    $this->drupalLogin($account);
    $this->drupalGet('/node/add/landing_page');
    $this->assertSession()->pageTextContains($description);
  }

}
