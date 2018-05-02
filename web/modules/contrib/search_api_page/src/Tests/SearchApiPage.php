<?php

/**
 * @file
 * Contains \Drupal\search_api_page\Tests\SearchApiPage.
 */

namespace Drupal\search_api_page\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Tests\ExampleContentTrait;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\simpletest\WebTestBase as SimpletestWebTestBase;

/**
 * Provides web tests for Search API Pages.
 *
 * @group search_api_page
 */
class SearchApiPage extends SimpletestWebTestBase {

  use StringTranslationTrait;
  use ExampleContentTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = ['search_api_page', 'node', 'search_api', 'search_api_db', 'block'];

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user without any permission..
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unauthorizedUser;

  /**
   * The anonymous user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymousUser;

  /**
   * A search database server.
   *
   * @var string
   */
  protected $serverId = 'database_search_server';

  /**
   * A search index ID.
   *
   * @var string
   */
  protected $indexId = 'database_search_index';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the users used for the tests.
    $this->adminUser = $this->drupalCreateUser([
      'administer search_api',
      'administer search_api_page',
      'access administration pages',
      'administer nodes',
      'access content overview',
      'administer content types',
      'administer blocks',
      'view search api pages',
    ]);
    $this->unauthorizedUser = $this->drupalCreateUser();
    $this->anonymousUser = $this->drupalCreateUser(['view search api pages']);

    // Create article content type and content.
    $this->drupalCreateContentType(array('type' => 'article'));
    for ($i = 1; $i < 50; $i++) {
      $this->drupalCreateNode(array(
        'title' => 'Item number' . $i,
        'type' => 'article',
        'body' => [['value' => 'Body number' . $i]]));
    }
  }

  /**
   * Test search api pages.
   */
  public function testSearchApiPage() {
    $this->drupalLogin($this->adminUser);

    // Setup search api server and index.
    $this->setupSearchAPI();

    $this->drupalGet('admin/config/search/search-api-pages');
    $this->assertResponse(200);

    $step1 = array(
      'label' => 'Search',
      'id' => 'search',
      'index' => 'database_search_index',
    );
    $this->drupalPostForm('admin/config/search/search-api-pages/add', $step1, 'Next');

    $step2 = array(
      'path' => 'search',
    );
    $this->drupalPostForm(NULL, $step2, 'Save');

    $this->drupalGet('search');
    $this->assertRaw('Enter the terms you wish to search for.');
    $this->assertNoRaw('Your search yielded no results.');
    $this->assertResponse(200);

    $this->drupalLogout();
    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet('search');
    $this->assertResponse(403);

    $this->drupalLogout();
    $this->drupalLogin($this->anonymousUser);
    $this->drupalGet('search');
    $this->assertResponse(200);

    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('search/nothing-found');
    $this->assertRaw('Enter the terms you wish to search for.');
    $this->assertRaw('Your search yielded no results.');
    $this->drupalGet('search');
    $this->assertNoRaw('Your search yielded no results.');

    $this->drupalPostForm('admin/config/search/search-api-pages/search', array('show_all_when_no_keys' => TRUE, 'show_search_form' => FALSE), 'Save');
    $this->drupalGet('search');
    $this->assertNoRaw('Your search yielded no results.');
    $this->assertNoRaw('Enter the terms you wish to search for.');
    $this->assertText('49 results found');

    $this->drupalGet('search/number10');
    $this->assertText('1 result found');

    $this->drupalPostForm('admin/config/search/search-api-pages/search', array('show_search_form' => TRUE), 'Save');

    $this->drupalGet('search/number11');
    $this->assertText('1 result found');
    $this->assertRaw('name="keys" value="number11"');

    // Cache should be cleared after the save.
    //$this->drupalGet('search/number10');
    //$this->assertText('1 result found');
    //$this->assertRaw('name="keys" value="number10"');
  }

  /**
   * Private method to setup Search API database and server.
   */
  private function setupSearchAPI() {
    $server = array(
      'name' => $this->serverId,
      'id' => $this->serverId,
      'backend' => 'search_api_db',
    );
    $this->drupalPostForm('admin/config/search/search-api/add-server', $server, 'Save');
    // The notice will be fixed when https://www.drupal.org/node/1299238 is in.

    $index = array(
      'name' => $this->indexId,
      'id' => $this->indexId,
      'datasources[]' => 'entity:node',
      'server' => $this->serverId,
    );
    $this->drupalPostForm('admin/config/search/search-api/add-index', $index, 'Save');

    // Add rendered item fields.
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/fields/add', ['query' => ['datasource' => '']]);
    $post = '&' . $this->serializePostValues(array('rendered_item' => $this->t('Add')));
    $this->drupalPostForm(NULL, array(), NULL, array(), array(), NULL, $post);
    //$this->drupalPostForm(NULL, [], );

    $this->drupalPostForm(NULL, ['view_mode[entity:node][article]' => 'default'], 'Save');
    $task_manager = \Drupal::getContainer()->get('search_api.index_task_manager');
    $task_manager->addItemsAll(Index::load($this->indexId));
    $this->indexItems($this->indexId);
  }

}
