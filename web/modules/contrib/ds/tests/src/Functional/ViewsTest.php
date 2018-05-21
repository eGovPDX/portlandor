<?php

namespace Drupal\Tests\ds\Functional;

use Drupal\views\Tests\ViewTestData;
use Drupal\views\ViewExecutable;

/**
 * Tests for Display Suite Views integration.
 *
 * @group ds
 */
class ViewsTest extends FastTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field_ui',
    'taxonomy',
    'block',
    'ds',
    'ds_test',
    'views',
    'views_ui',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['ds-testing'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Ensure that the plugin definitions are cleared.
    foreach (ViewExecutable::getPluginTypes() as $plugin_type) {
      $this->container->get("plugin.manager.views.$plugin_type")->clearCachedDefinitions();
    }

    ViewTestData::createTestViews(get_class($this), ['ds_test']);
  }

  /**
   * Test views integration.
   */
  public function testDsViews() {
    $tag1 = $this->createTerm($this->vocabulary);
    $tag2 = $this->createTerm($this->vocabulary);

    $edit_tag_1 = [
      'field_tags[0][target_id]' => $tag1->getName(),
    ];
    $edit_tag_2 = [
      'field_tags[0][target_id]' => $tag2->getName(),
    ];

    // Create 3 nodes.
    $settings_1 = [
      'type' => 'article',
      'title' => 'Article 1',
      'created' => \Drupal::time()->getRequestTime(),
    ];
    $node_1 = $this->drupalCreateNode($settings_1);
    $this->drupalPostForm('node/' . $node_1->id() . '/edit', $edit_tag_1, t('Save and keep published'));
    $settings_2 = [
      'type' => 'article',
      'title' => 'Article 2',
      'created' => \Drupal::time()->getRequestTime() + 3600,
    ];
    $node_2 = $this->drupalCreateNode($settings_2);
    $this->drupalPostForm('node/' . $node_2->id() . '/edit', $edit_tag_1, t('Save and keep published'));
    $settings_3 = [
      'type' => 'article',
      'title' => 'Article 3',
      'created' => \Drupal::time()->getRequestTime() + 7200,
    ];
    $node_3 = $this->drupalCreateNode($settings_3);
    $this->drupalPostForm('node/' . $node_3->id() . '/edit', $edit_tag_2, t('Save and keep published'));

    // Configure teaser and full layout.
    $layout = [
      'layout' => 'ds_2col',
    ];
    $fields = [
      'fields[node_title][region]' => 'left',
      'fields[body][region]' => 'right',
    ];
    $assert = [
      'regions' => [
        'left' => '<td colspan="8">' . t('Left') . '</td>',
        'right' => '<td colspan="8">' . t('Right') . '</td>',
      ],
    ];
    $this->dsSelectLayout($layout, $assert, 'admin/structure/types/manage/article/display/teaser');
    $this->dsConfigureUi($fields, 'admin/structure/types/manage/article/display/teaser');
    $layout = [
      'layout' => 'ds_4col',
    ];
    $fields = [
      'fields[node_post_date][region]' => 'first',
      'fields[body][region]' => 'second',
      'fields[node_author][region]' => 'third',
      'fields[node_links][region]' => 'fourth',
    ];
    $assert = [
      'regions' => [
        'first' => '<td colspan="8">' . t('First') . '</td>',
        'second' => '<td colspan="8">' . t('Second') . '</td>',
        'third' => '<td colspan="8">' . t('Third') . '</td>',
        'fourth' => '<td colspan="8">' . t('Fourth') . '</td>',
      ],
    ];
    $this->dsSelectLayout($layout, $assert);
    $this->dsConfigureUi($fields);

    // Get default teaser view.
    $this->drupalGet('ds-testing');
    foreach (['group-left', 'group-right'] as $region) {
      $this->assertSession()->responseContains($region);
    }
    $this->assertSession()->responseContains('Article 1');
    $this->assertSession()->responseContains('Article 2');
    $this->assertSession()->responseContains('Article 3');

    // Get alternating view.
    $this->drupalGet('ds-testing-2');
    $regions = [
      'group-left',
      'group-right',
      'first',
      'second',
      'third',
      'fourth',
    ];
    foreach ($regions as $region) {
      $this->assertSession()->responseContains($region);
    }
    $this->assertSession()->responseNotContains('Article 1');
    $this->assertSession()->responseContains('Article 2');
    $this->assertSession()->responseContains('Article 3');

    // Get grouping view (without changing header function).
    $this->drupalGet('ds-testing-3');
    foreach (['group-left', 'group-right'] as $region) {
      $this->assertSession()->responseContains($region);
    }
    $this->assertSession()->responseContains('Article 1');
    $this->assertSession()->responseContains('Article 2');
    $this->assertSession()->responseContains('Article 3');
    $this->assertSession()->responseContains('<h2 class="grouping-title">' . $tag1->id() . '</h2>');
    $this->assertSession()->responseContains('<h2 class="grouping-title">' . $tag2->id() . '</h2>');

    // Get grouping view (with changing header function).
    $this->drupalGet('ds-testing-4');
    foreach (['group-left', 'group-right'] as $region) {
      $this->assertSession()->responseContains($region);
    }
    $this->assertSession()->responseContains('Article 1');
    $this->assertSession()->responseContains('Article 2');
    $this->assertSession()->responseContains('Article 3');
    $this->assertSession()->responseContains('<h2 class="grouping-title">' . $tag1->getName() . '</h2>');
    $this->assertSession()->responseContains('<h2 class="grouping-title">' . $tag2->getName() . '</h2>');

    // Get advanced function view.
    $this->drupalGet('ds-testing-5');
    $this->assertSession()->responseContains('Advanced display for id 1');
    $this->assertSession()->responseContains('Advanced display for id 2');
    $this->assertSession()->responseContains('Advanced display for id 3');
  }

}
