<?php

namespace Drupal\Tests\media_entity_twitter\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;

/**
 * Tests for Twitter embed formatter.
 *
 * @group media_entity_twitter
 */
class TweetEmbedFormatterTest extends MediaFunctionalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'media_entity_twitter',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests adding and editing a twitter embed formatter.
   */
  public function testManageEmbedFormatter() {
    // Test and create one media type.
    $bundle = $this->createMediaType(['bundle' => 'twitter'], 'twitter');

    // We need to fix widget and formatter config for the default field.
    $source = $bundle->getSource();
    $source_field = $source->getSourceFieldDefinition($bundle);
    // Use the default widget and settings.
    $component = \Drupal::service('plugin.manager.field.widget')
      ->prepareConfiguration('string', []);

    // @todo Replace entity_get_form_display() when #2367933 is done.
    // https://www.drupal.org/node/2872159.
    entity_get_form_display('media', $bundle->id(), 'default')
      ->setComponent($source_field->getName(), $component)
      ->save();

    // Assert that the media type has the expected values before proceeding.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id());
    $this->assertFieldByName('label', $bundle->label());
    $this->assertFieldByName('source', 'twitter');

    // Add and save string_long field type settings (Embed code).
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/fields/add-field');
    $edit_conf = [
      'new_storage_type' => 'string_long',
      'label' => 'Embed code',
      'field_name' => 'embed_code',
    ];
    $this->drupalPostForm(NULL, $edit_conf, t('Save and continue'));
    $this->assertText('These settings apply to the ' . $edit_conf['label'] . ' field everywhere it is used.');
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->assertText('Updated field ' . $edit_conf['label'] . ' field settings.');

    // Set the new string_long field type as required.
    $edit = [
      'required' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertText('Saved ' . $edit_conf['label'] . ' configuration.');

    // Assert that the new field types configurations have been successfully
    // saved.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/fields');
    $xpath = $this->xpath('//*[@id=:id]/td', [':id' => 'field-media-twitter']);
    $this->assertEqual((string) $xpath[0]->getText(), 'Tweet Url');
    $this->assertEqual((string) $xpath[1]->getText(), 'field_media_twitter');
    $this->assertEqual((string) $xpath[2]->find('css', 'a')->getText(), 'Text (plain)');

    $xpath = $this->xpath('//*[@id=:id]/td', [':id' => 'field-embed-code']);
    $this->assertEqual((string) $xpath[0]->getText(), 'Embed code');
    $this->assertEqual((string) $xpath[1]->getText(), 'field_embed_code');
    $this->assertEqual((string) $xpath[2]->find('css', 'a')->getText(), 'Text (plain, long)');

    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/display');

    // Set and save the settings of the new field types.
    $edit = [
      'fields[field_media_twitter][parent]' => 'content',
      'fields[field_media_twitter][region]' => 'content',
      'fields[field_media_twitter][label]' => 'above',
      'fields[field_media_twitter][type]' => 'twitter_embed',
      'fields[field_embed_code][label]' => 'above',
      'fields[field_embed_code][type]' => 'twitter_embed',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Your settings have been saved.');

    // Create and save the media with a twitter media code.
    $this->drupalGet('media/add/' . $bundle->id());

    // Random image url from twitter.
    $tweet_url = 'https://twitter.com/RamzyStinson/status/670650348319576064';

    // Random image from twitter.
    $tweet = '<blockquote class="twitter-tweet" lang="it"><p lang="en" dir="ltr">' .
             'Midnight project. I ain&#39;t got no oven. So I improvise making this milo crunchy kek batik. hahahaha ' .
             '<a href="https://twitter.com/hashtag/itssomething?src=hash">#itssomething</a> ' .
             '<a href="https://t.co/Nvn4Q1v2ae">pic.twitter.com/Nvn4Q1v2ae</a></p>&mdash; Zi (@RamzyStinson) ' .
             '<a href="https://twitter.com/RamzyStinson/status/670650348319576064">' .
             '28 Novembre 2015</a></blockquote><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>';

    $edit = [
      'name[0][value]' => 'Title',
      'field_media_twitter[0][value]' => $tweet_url,
      'field_embed_code[0][value]' => $tweet,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Assert that the media has been successfully saved.
    $this->assertText('Title');

    // Assert that the link url formatter exists on this page.
    $this->assertText('Tweet Url');
    $this->assertRaw('<a href="https://twitter.com/RamzyStinson/statuses/670650348319576064">', 'Link in embedded Tweet found.');

    // Assert that the string_long code formatter exists on this page.
    $this->assertText('Embed code');
    $this->assertRaw('<blockquote class="twitter-tweet', 'Embedded Tweet found.');
  }

}
