<?php

namespace Drupal\paragraphs\Tests\Experimental;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\user\RoleInterface;
use Drupal\user\Entity\Role;

/**
 * Tests the access check of paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalAccessTest extends ParagraphsExperimentalTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'image',
    'paragraphs_demo',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the paragraph translation.
   */
  public function testParagraphAccessCheck() {
    $permissions = [
      'administer site configuration',
      'administer node display',
      'administer paragraph display',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
    ];
    $this->loginAsAdmin($permissions);

    // Remove the "access content" for anonymous users. That results in
    // anonymous users not being able to "view" the host entity.
    /* @var Role $role */
    $role = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->load(RoleInterface::ANONYMOUS_ID);
    $role->revokePermission('access content');
    $role->save();

    // Set field_images from demo to private file storage.
    $edit = array(
      'settings[uri_scheme]' => 'private',
    );
    $this->drupalPostForm('admin/structure/paragraphs_type/images/fields/paragraph.images.field_images_demo/storage', $edit, t('Save field settings'));

    // Use the experimental widget.
    $form_display = EntityFormDisplay::load('node.paragraphed_content_demo.default')
      ->setComponent('field_paragraphs_demo', ['type' => 'paragraphs']);
    $form_display->save();
    // Create a new demo node.
    $this->drupalGet('node/add/paragraphed_content_demo');

    // Add a new paragraphs images item.
    $this->drupalPostForm(NULL, NULL, t('Add Images'));

    $images = $this->drupalGetTestFiles('image');

    // Create a file, upload it.
    file_unmanaged_copy($images[0]->uri, 'temporary://privateImage.jpg');
    $file_path = $this->container->get('file_system')
      ->realpath('temporary://privateImage.jpg');

    // Create a file, upload it.
    file_unmanaged_copy($images[1]->uri, 'temporary://privateImage2.jpg');
    $file_path_2 = $this->container->get('file_system')
      ->realpath('temporary://privateImage2.jpg');

    $edit = array(
      'title[0][value]' => 'Security test node',
      'files[field_paragraphs_demo_0_subform_field_images_demo_0][]' => [$file_path, $file_path_2],
    );

    $this->drupalPostForm(NULL, $edit, t('Upload'));
    $this->drupalPostForm(NULL,  [], t('Preview'));
    $img1_url = file_create_url(\Drupal::token()->replace('private://privateImage.jpg'));
    $image_url = file_url_transform_relative($img1_url);
    $this->assertRaw($image_url, 'Image was found in preview');
    $this->clickLink(t('Back to content editing'));
    $this->drupalPostForm(NULL, [], t('Save'));

    $node = $this->drupalGetNodeByTitle('Security test node');

    $this->drupalGet('node/' . $node->id());

    // Check the text and image after publish.
    $this->assertRaw($image_url, 'Image was found in content');

    $this->drupalGet($image_url);
    $this->assertResponse(200, 'Image could be downloaded');

    // Logout to become anonymous.
    $this->drupalLogout();

    // @todo Requesting the same $img_url again triggers a caching problem on
    // drupal.org test bot, thus we request a different file here.
    $img_url = file_create_url(\Drupal::token()->replace('private://privateImage2.jpg'));
    $image_url = file_url_transform_relative($img_url);
    // Check the text and image after publish. Anonymous should not see content.
    $this->assertNoRaw($image_url, 'Image was not found in content');

    $this->drupalGet($image_url);
    $this->assertResponse(403, 'Image could not be downloaded');

    // Login as admin with no delete permissions.
    $this->loginAsAdmin($permissions);
    // Create a new demo node.
    $this->drupalGet('node/add/paragraphed_content_demo');
    $this->drupalPostForm(NULL, NULL, t('Add Text'));
    $this->assertText('Text');
    $edit = [
      'title[0][value]' => 'delete_permissions',
      'field_paragraphs_demo[0][subform][field_text_demo][0][value]' => 'Test',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Edit the node.
    $this->clickLink(t('Edit'));
    // Check the remove button is present.
    $this->assertNotNull($this->xpath('//*[@name="field_paragraphs_demo_0_remove"]'));
    // Delete the Paragraph and save.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_remove');
    $this->drupalPostForm(NULL, [], t('Save'));
    $node = $this->getNodeByTitle('delete_permissions');
    $this->assertUrl('node/' . $node->id());
  }
}
