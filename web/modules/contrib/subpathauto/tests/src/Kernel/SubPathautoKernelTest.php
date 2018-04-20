<?php

namespace Drupal\Tests\subpathauto\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\UserCreationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\subpathauto\PathProcessor
 * @group subpathauto
 */
class SubPathautoKernelTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'subpathauto', 'node', 'user'];

  /**
   * @var \Drupal\Core\Path\AliasStorage
   */
  protected $aliasStorage;

  /**
   * @var \Drupal\Core\Path\AliasWhitelistInterface
   */
  protected $aliasWhiteList;

  /**
   * The service under testing.
   *
   * @var \Drupal\subpathauto\PathProcessor
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    $this->installConfig('subpathauto');

    // Create the node bundles required for testing.
    $type = NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ]);
    $type->save();

    $this->aliasStorage = $this->container->get('path.alias_storage');
    $this->sut = $this->container->get('path_processor_subpathauto');
    $this->aliasWhiteList = $this->container->get('path.alias_whitelist');

    Node::create(['type' => 'page', 'title' => 'test'])->save();
    $this->aliasStorage->save('/node/1', '/kittens');
    $this->aliasWhiteList->set('node', TRUE);
  }

  /**
   * @covers ::processInbound
   */
  public function testProcessInbound() {
    // Alias should not be converted for aliases that are not valid.
    $processed = $this->sut->processInbound('/kittens/are-fake', Request::create('/kittens/are-fake'));
    $this->assertEquals('/kittens/are-fake', $processed);

    // Alias should be converted on a request wih language prefix.
    $processed = $this->sut->processInbound('/kittens/edit', Request::create('/en/kittens/edit'));
    $this->assertEquals('/node/1/edit', $processed);

    // Alias should be converted even when the user doesn't have permissions to
    // view the page.
    $processed = $this->sut->processInbound('/kittens/edit', Request::create('/kittens/edit'));
    $this->assertEquals('/node/1/edit', $processed);

    // Alias should be converted because of admin user has access to edit the
    // node.
    $admin_user = $this->createUser();
    \Drupal::currentUser()->setAccount($admin_user);
    $processed = $this->sut->processInbound('/kittens/edit', Request::create('/kittens/edit'));
    $this->assertEquals('/node/1/edit', $processed);
  }

  /**
   * @covers ::processOutbound
   */
  public function testProcessOutbound() {
    // Alias should not be converted for invalid paths.
    $processed = $this->sut->processOutbound('/kittens/are-fake');
    $this->assertEquals('/kittens/are-fake', $processed);

    // Alias should be converted even when the user doesn't have permissions to
    // view the page.
    $processed = $this->sut->processOutbound('/node/1/edit');
    $this->assertEquals('/kittens/edit', $processed);

    // Alias should be converted also for user that has access to view the page.
    $admin_user = $this->createUser();
    \Drupal::currentUser()->setAccount($admin_user);
    $processed = $this->sut->processOutbound('/node/1/edit');
    $this->assertEquals('/kittens/edit', $processed);

    // Check that alias is converted for absolute paths. The Redirect module,
    // for instance, requests an absolute path when it checks if a redirection
    // is needed.
    $options = ['absolute' => TRUE];
    $processed = $this->sut->processOutbound('/node/1/edit', $options);
    $this->assertEquals('/kittens/edit', $processed);
  }

}
