<?php

namespace Drupal\Tests\lightning_layout\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * @group lightning
 * @group lightning_layout
 */
class EntityBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_block',
    'lightning_layout',
    'media',
    'taxonomy',
  ];

  /**
   * Tests that entity_block derivatives are filtered by config.
   */
  public function testEntityTypeFiltering() {
    $allowed = $this->config('lightning_layout.settings')
      ->get('entity_blocks');

    $definitions = $this->container->get('plugin.manager.block')
      ->getDefinitions();

    $entity_blocks = preg_grep('/^entity_block:/', array_keys($definitions));

    $this->assertSame(count($allowed), count($entity_blocks));

    foreach ($allowed as $entity_type_id) {
      $this->assertArrayHasKey("entity_block:$entity_type_id", $definitions);
    }
  }

  /**
   * Tests that entity blocks do proper access control.
   */
  public function testAccessControl() {
    $node_type = $this->drupalCreateContentType();

    $node = Node::create([
      'type' => $node_type->id(),
      'title' => $this->randomString(),
    ]);
    $node->setUnpublished();
    $node->save();

    $block = $this->drupalPlaceBlock('entity_block:node', [
      'entity' => $node->id(),
    ]);

    $account = $this->container->get('current_user');
    $this->assertTrue($account->isAnonymous());
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access content']);

    // The node is unpublished, so the anonymous user cannot access the block.
    $this->assertEmpty($block->getPlugin()->build());

    // Publish the node, reset static caches, and ensure that the anonymous
    // user can now access the block.
    $node->setPublished();
    $node->save();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager->getStorage('node')->resetCache();
    $entity_type_manager->getAccessControlHandler('node')->resetCache();

    $this->assertNotEmpty($block->getPlugin()->build());
  }

}
