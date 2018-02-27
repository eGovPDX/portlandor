<?php

namespace Drupal\Tests\gnode\Kernel;

/**
 * Tests the grants people receive for group nodes.
 *
 * @group gnode
 */
class GroupNodeGrantsTest extends GroupNodeAccessTestBase {

  /**
   * Tests the assignment of the bypass access grant.
   */
  public function testBypassGrant() {
    $account = $this->createUser([], ['bypass group access']);
    $grants = gnode_node_grants($account, 'view');
    $this->assertEquals(['gnode_bypass' => [GNODE_MASTER_GRANT_ID]], $grants, 'Users who can bypass group access receive the bypass grant.');
  }

  /**
   * Tests the existence of specific grant realms.
   */
  public function testGrantRealms() {
    $grants = gnode_node_grants($this->account, 'view');
    $this->assertArrayHasKey('gnode:a', $grants, 'Grants were handed out for node type a.');
    $this->assertArrayHasKey('gnode:b', $grants, 'Grants were handed out for node type b.');
    $this->assertArrayNotHasKey('gnode:c', $grants, 'Grants were not handed out for node type c.');
  }

  /**
   * Tests that a user receives the right view grants for group nodes.
   */
  public function testViewGrants() {
    $grants = gnode_node_grants($this->account, 'view');
    $this->assertTrue(in_array($this->groupA1->id(), $grants['gnode:a']), 'A-group: Member can view A-nodes.');
    $this->assertTrue(in_array($this->groupA1->id(), $grants['gnode:b']), 'A-group: Member can view B-nodes.');
    $this->assertTrue(in_array($this->groupA1->id(), $grants['gnode_unpublished:a']), 'A-group: Member can view unpublished A-nodes.');
    $this->assertTrue(in_array($this->groupA2->id(), $grants['gnode:a']), 'A-group: Outsider can view A-nodes.');
    $this->assertTrue(in_array($this->groupA2->id(), $grants['gnode:b']), 'A-group: Outsider can view B-nodes.');
    $this->assertTrue(in_array($this->groupB2->id(), $grants['gnode:b']), 'B-group: Outsider can view B-nodes.');

    // We are testing a bit more specifically here to make sure that the system
    // is only adding those group IDs the user has access in. Seeing as further
    // tests rely on the same system, we are not testing this again.
    $this->assertFalse(in_array($this->groupA2->id(), $grants['gnode_unpublished:a']), 'A-group: Outsider can not view unpublished A-nodes.');
    $this->assertFalse(in_array($this->groupB2->id(), $grants['gnode:a']), 'B-group: Outsider can not view A-nodes.');
    $this->assertFalse(in_array($this->groupB1->id(), $grants['gnode:b']), 'B-group: Member can not view B-nodes.');
  }

  /**
   * Tests that a user receives the right update grants for group nodes.
   */
  public function testUpdateGrants() {
    $grants = gnode_node_grants($this->account, 'update');

    // Test 'update any' permissions.
    $this->assertTrue(in_array($this->groupA2->id(), $grants['gnode:a']), 'A-group: Outsider can update any A-nodes.');
    $this->assertTrue(in_array($this->groupB1->id(), $grants['gnode:b']), 'B-group: Member can update any B-nodes.');

    // Test 'update own' permissions.
    $this->assertTrue(in_array($this->groupA1->id(), $grants['gnode_author:2:a']), 'A-group: Member can update own A-nodes.');
    $this->assertTrue(in_array($this->groupB2->id(), $grants['gnode_author:2:b']), 'B-group: Outsider can update own B-nodes.');
  }

  /**
   * Tests that a user receives the right delete grants for group nodes.
   */
  public function testDeleteGrants() {
    $grants = gnode_node_grants($this->account, 'delete');

    // Test 'delete any' permissions.
    $this->assertTrue(in_array($this->groupA2->id(), $grants['gnode:a']), 'A-group: Outsider can delete any A-nodes.');
    $this->assertTrue(in_array($this->groupB1->id(), $grants['gnode:b']), 'B-group: Member can delete any B-nodes.');

    // Test 'delete own' permissions.
    $this->assertTrue(in_array($this->groupA1->id(), $grants['gnode_author:2:a']), 'A-group: Member can delete own A-nodes.');
    $this->assertTrue(in_array($this->groupB2->id(), $grants['gnode_author:2:b']), 'B-group: Outsider can delete own B-nodes.');
  }

}
