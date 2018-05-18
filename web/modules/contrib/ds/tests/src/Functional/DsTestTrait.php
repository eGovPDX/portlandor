<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Provides common functionality for the Display Suite test classes.
 */
trait DsTestTrait {

  /**
   * Select a layout.
   */
  public function dsSelectLayout($edit = [], $assert = [], $url = 'admin/structure/types/manage/article/display', $options = []) {
    $edit += [
      'layout' => 'ds_2col_stacked',
    ];

    $this->drupalPostForm($url, $edit, t('Save'), $options);

    $assert += [
      'regions' => [
        'header' => '<td colspan="8">' . t('Header') . '</td>',
        'left' => '<td colspan="8">' . t('Left') . '</td>',
        'right' => '<td colspan="8">' . t('Right') . '</td>',
        'footer' => '<td colspan="8">' . t('Footer') . '</td>',
      ],
    ];

    foreach ($assert['regions'] as $region => $raw) {
      $this->assertSession()->responseContains($region);
    }
  }

  /**
   * Configure classes.
   */
  public function dsConfigureClasses($edit = []) {

    $edit += [
      'regions' => "class_name_1\nclass_name_2|Friendly name",
    ];

    $this->drupalPostForm('admin/structure/ds/classes', $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->responseContains('class_name_1', 'Class name 1 found');
    $this->assertSession()->responseContains('class_name_2', 'Class name 1 found');
  }

  /**
   * Configure classes on a layout.
   */
  public function dsSelectClasses($edit = [], $url = 'admin/structure/types/manage/article/display') {

    $edit += [
      "layout_configuration[ds_classes][header][]" => 'class_name_1',
      "layout_configuration[ds_classes][footer][]" => 'class_name_2',
    ];

    $this->drupalPostForm($url, $edit, t('Save'));
  }

  /**
   * Configure Field UI.
   */
  public function dsConfigureUi($edit, $url = 'admin/structure/types/manage/article/display') {
    $this->drupalPostForm($url, $edit,'Save');
  }

  /**
   * Edit field formatter settings.
   */
  public function dsEditFormatterSettings($edit, $field_name = 'body', $url = 'admin/structure/types/manage/article/display') {
    $element_value = 'edit ' . $field_name;
    $this->drupalPostForm($url, [], $element_value);

    if (isset($edit['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ft][id]'])) {
      $this->drupalPostForm(NULL, ['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ft][id]' => $edit['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ft][id]']], t('Update'));
      $this->drupalPostForm(NULL, [], $element_value);
      unset($edit['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ft][id]']);
    }

    $this->drupalPostForm(NULL, $edit, t('Update'));
    $this->drupalPostForm(NULL, [], t('Save'));
  }

  /**
   * Edit limit.
   */
  public function dsEditLimitSettings($edit, $field_name = 'body', $url = 'admin/structure/types/manage/article/display') {
    $element_value = 'edit ' . $field_name;
    $this->drupalPostForm($url, [], $element_value);

    if (isset($edit['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ft][id]'])) {
      $this->drupalPostForm(NULL, ['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ds_limit]' => $edit['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ds_limit]']], t('Update'));
      $this->drupalPostForm(NULL, [], $element_value);
      unset($edit['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ds_limit]']);
    }

    $this->drupalPostForm(NULL, $edit, t('Update'));
    $this->drupalPostForm(NULL, [], t('Save'));
  }

  /**
   * Create a token field.
   *
   * @param array $edit
   *   An optional array of field properties.
   * @param string $url
   *   The url to post to.
   */
  public function dsCreateTokenField(array $edit = [], $url = 'admin/structure/ds/fields/manage_token') {
    $edit += [
      'name' => 'Test field',
      'id' => 'test_field',
      'entities[node]' => '1',
      'content[value]' => 'Test field',
    ];

    $this->drupalPostForm($url, $edit, t('Save'));
    $this->assertSession()->responseContains(t('The field %name has been saved', ['%name' => $edit['name']]));
  }

  /**
   * Create a block field.
   *
   * @param array $edit
   *   An optional array of field properties.
   * @param string $url
   *   The URL of the manage block page.
   */
  public function dsCreateBlockField(array $edit = [], $url = 'admin/structure/ds/fields/manage_block') {
    $edit += [
      'name' => 'Test block field',
      'id' => 'test_block_field',
      'entities[node]' => '1',
      'block' => 'system_powered_by_block',
    ];

    $this->drupalPostForm($url, $edit, t('Save'));
    $this->assertSession()->responseContains(t('The field %name has been saved', ['%name' => $edit['name']]));
  }

  /**
   * Utility function to setup for all kinds of tests.
   *
   * @param string $label
   *   How the body label must be set.
   */
  public function entitiesTestSetup($label = 'above') {

    // Create a node.
    $settings = ['type' => 'article', 'promote' => 1];
    $node = $this->drupalCreateNode($settings);

    // Create field CSS classes.
    $edit = ['fields' => "test_field_class\ntest_field_class_2|Field class 2\n[node:nid]"];
    $this->drupalPostForm('admin/structure/ds/classes', $edit,'Save configuration');

    // Create a token field.
    $token_field = [
      'name' => 'Token field',
      'id' => 'token_field',
      'entities[node]' => '1',
      'content[value]' => '[node:title]',
    ];
    $this->dsCreateTokenField($token_field);

    // Select layout.
    $this->dsSelectLayout();

    // Configure fields.
    $fields = [
      'fields[dynamic_token_field:node-token_field][region]' => 'header',
      'fields[body][region]' => 'right',
      'fields[node_link][region]' => 'footer',
      'fields[body][label]' => $label,
      'fields[node_submitted_by][region]' => 'header',
    ];
    $this->dsConfigureUi($fields);

    return $node;
  }

  /**
   * Utility function to clear field settings.
   */
  public function entitiesClearFieldSettings() {
    $display = EntityViewDisplay::load('node.article.default');

    // Remove all third party settings from components.
    foreach ($display->getComponents() as $key => $info) {
      $info['third_party_settings'] = [];
      $display->setComponent($key, $info);
    }

    // Remove entity display third party settings.
    $tps = $display->getThirdPartySettings('ds');
    if (!empty($tps)) {
      foreach (array_keys($tps) as $key) {
        $display->unsetThirdPartySetting('ds', $key);
      }
    }

    // Save.
    $display->save();
  }

  /**
   * Set the label.
   */
  public function entitiesSetLabelClass($label, $field_name, $text = '', $class = '', $show_colon = FALSE) {
    $edit = [
      'fields[' . $field_name . '][label]' => $label,
    ];
    if (!empty($text)) {
      $edit['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ft][settings][lb]'] = $text;
    }
    if (!empty($class)) {
      $edit['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ft][settings][classes][]'] = $class;
    }
    if ($show_colon) {
      $edit['fields[' . $field_name . '][settings_edit_form][third_party_settings][ds][ft][settings][lb-col]'] = '1';
    }
    $this->dsEditFormatterSettings($edit);
  }

}
