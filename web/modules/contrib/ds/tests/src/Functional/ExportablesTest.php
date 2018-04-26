<?php

namespace Drupal\Tests\ds\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests for exportables in Display Suite.
 *
 * @group ds
 */
class ExportablesTest extends FastTestBase {

  /**
   * Enables the exportables module.
   */
  public function dsExportablesSetup() {
    /* @var $display EntityViewDisplay */
    $display = EntityViewDisplay::load('node.article.default');
    $display->delete();
    \Drupal::service('module_installer')->install(['ds_exportables_test']);
  }

  /**
   * Test layout and field settings configuration.
   */
  public function testDsExportablesLayoutFieldsettings() {
    $this->dsExportablesSetup();

    // Look for default custom field.
    $this->drupalGet('admin/structure/ds/fields');
    $this->assertSession()->pageTextContains('Exportable field');
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->pageTextContains('Exportable field');

    $settings = [
      'type' => 'article',
      'title' => 'Exportable',
    ];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->responseNotContains('group-header');
    $this->assertSession()->responseNotContains('group-footer');
    $link = $this->xpath('//h3/a[text()=:text]', [
      ':text' => 'Exportable',
    ]);
    $this->assertEquals(count($link), 1, 'Default title with h3 found');
    $link = $this->xpath('//a[text()=:text]', [
      ':text' => 'Read more',
    ]);
    $this->assertEquals(count($link), 1, 'Default read more found');

    // Override default layout.
    $layout = [
      'layout' => 'ds_2col_stacked',
    ];

    $assert = [
      'regions' => [
        'header' => '<td colspan="8">' . t('Header') . '</td>',
        'left' => '<td colspan="8">' . t('Left') . '</td>',
        'right' => '<td colspan="8">' . t('Right') . '</td>',
        'footer' => '<td colspan="8">' . t('Footer') . '</td>',
      ],
    ];

    $fields = [
      'fields[node_post_date][region]' => 'header',
      'fields[node_author][region]' => 'left',
      'fields[node_link][region]' => 'left',
      'fields[body][region]' => 'right',
    ];

    $this->dsSelectLayout($layout, $assert);
    $this->dsConfigureUi($fields);

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->responseContains('group-header');
    $this->assertSession()->responseContains('group-footer');
  }

}
