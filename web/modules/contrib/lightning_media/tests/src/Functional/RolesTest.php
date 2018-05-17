<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Functional\MediaFunctionalTestCreateMediaTypeTrait;
use Drupal\user\Entity\Role;

/**
 * @group lightning
 * @group lightning_media
 */
class RolesTest extends BrowserTestBase {

  use MediaFunctionalTestCreateMediaTypeTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'lightning_media',
    'media_test_source',
  ];

  /**
   * The ID of the media type created for the test.
   *
   * @var string
   */
  protected $mediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mediaType = $this->createMediaType()->id();

    $dir = __DIR__ . '/../../../' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
    $storage = new FileStorage($dir);

    Role::create($storage->read('user.role.media_creator'))->save();
    Role::create($storage->read('user.role.media_manager'))->save();

    $this->drupalPlaceBlock('local_tasks_block');
  }

  public function testRoles() {
    $account = $this->drupalCreateUser();
    $account->addRole('media_creator');
    $account->save();
    $this->drupalLogin($account);

    $media = Media::create([
      'bundle' => $this->mediaType,
      'name' => $this->getRandomGenerator()->word(16),
      'uid' => $account->id(),
    ]);
    $media->setPublished();
    $media->save();

    $assert = $this->assertSession();
    $this->drupalGet('/admin/content/media');
    $this->clickLink($media->label());
    $assert->statusCodeEquals(200);
    $assert->linkExists('Edit');
    $assert->linkExists('Delete');

    $this->drupalLogout();

    $account = $this->drupalCreateUser();
    $account->addRole('media_manager');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content/media');
    $this->clickLink($media->label());
    $assert->statusCodeEquals(200);
    $assert->linkExists('Edit');
    $assert->linkExists('Delete');
  }

}
