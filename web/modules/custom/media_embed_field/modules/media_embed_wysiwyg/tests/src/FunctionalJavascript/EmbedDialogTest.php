<?php

namespace Drupal\Tests\media_embed_wysiwyg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Test the dialog form.
 *
 * @group media_embed_wysiwyg
 */
class EmbedDialogTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'media_embed_field',
    'media_embed_wysiwyg',
    'editor',
    'ckeditor',
    'field_ui',
    'node',
    'image',
  ];

  /**
   * An admin account for testing.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array_keys($this->container->get('user.permissions')->getPermissions()));
    $this->drupalLogin($this->adminUser);
    $this->createContentType(['type' => 'page']);
    \Drupal::configFactory()->getEditable('image.settings')->set('suppress_itok_output', TRUE)->save();

    // Assert access is denied without enabling the filter.
    $this->drupalGet('media-embed-wysiwyg/dialog/plain_text');
    $this->assertSession()->pageTextContains('Access denied');

    // Enable the filter.
    $this->drupalGet('admin/config/content/formats/manage/plain_text');
    $this->find('[name="editor[editor]"]')->setValue('ckeditor');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getDriver()->executeScript("jQuery('.form-item-editor-settings-toolbar-button-groups').show();");
    $this->submitForm([
      'filters[media_embed_wysiwyg][status]' => TRUE,
      'filters[filter_html_escape][status]' => FALSE,
      'editor[settings][toolbar][button_groups]' => '[[{"name":"Group","items":["media_embed","Source"]}]]',
    ], t('Save configuration'));

    // Visit the modal again.
    $this->drupalGet('media-embed-wysiwyg/dialog/plain_text');
    $this->assertSession()->pageTextNotContains('Access denied');
  }

  /**
   * Test the WYSIWYG embed modal.
   */
  public function testEmbedDialog() {
    // Use the modal to embed into a page.
    $this->drupalGet('node/add/page');
    $this->find('.cke_button__media_embed')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Assert all the form fields appear on the modal.
    $this->assertSession()->pageTextContains('Autoplay');
    $this->assertSession()->pageTextContains('Responsive Media');
    $this->assertSession()->pageTextContains('Media URL');

    // Attempt to submit the modal with no values.
    $this->find('input[name="media_url"]')->setValue('');
    $this->find('button.form-submit')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Media URL field is required.');

    // Submit the form with an invalid media URL.
    $this->find('input[name="media_url"]')->setValue('http://example.com/');
    $this->find('button.form-submit')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Could not find a media provider to handle the given URL.');
    $this->assertContains('http://example.com/', $this->getSession()->getPage()->getHtml());

    // Submit a valid URL.
    $this->find('input[name="media_url"]')->setValue('https://www.youtube.com/watch?v=iaf3Sl2r3jE&t=1553s');
    $this->find('button.form-submit')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    // View the source of the ckeditor and find the output.
    $this->find('.cke_button__source_label')->click();
    $base_path = \Drupal::request()->getBasePath();
    $this->assertEquals('<p>{"preview_thumbnail":"' . rtrim($base_path, '/') . '/' . $this->publicFilesDirectory . '/styles/media_embed_wysiwyg_preview/public/media_thumbnails/iaf3Sl2r3jE.jpg","media_url":"https://www.youtube.com/watch?v=iaf3Sl2r3jE&amp;t=1553s","settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"settings_summary":["Embedded Media (Responsive, autoplaying)."]}</p>', trim($this->getSession()->getPage()->find('css', '.cke_source')->getValue()));
  }

  /**
   * Test the WYSIWYG integration works with nested markup.
   */
  public function testNestedMarkup() {
    $nested_content = '<div class="nested-content">
<p>{"preview_thumbnail":"/thumb.jpg","media_url":"https://www.youtube.com/watch?v=iaf3Sl2r3jE","settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"settings_summary":["Embedded Media (Responsive, autoplaying)."]}</p>
</div>';
    $node = $this->createNode([
      'type' => 'page',
      'body' => ['value' => $nested_content],
    ]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->find('.cke_button__source_label')->click();
    $this->assertEquals($nested_content, trim($this->getSession()->getPage()->find('css', '.cke_source')->getValue()));
  }

  /**
   * Find an element based on a CSS selector.
   *
   * @param string $css_selector
   *   A css selector to find an element for.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The found element or null.
   */
  protected function find($css_selector) {
    return $this->getSession()->getPage()->find('css', $css_selector);
  }

}
