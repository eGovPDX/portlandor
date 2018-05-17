<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Entity\GroupTypeInterface;

/**
 * Tests the general behavior of group type entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupType
 * @group group
 */
class GroupTypeTest extends GroupKernelTestBase {

  /**
   * The 'default' group type from the group_test_config test module.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->groupType = $this->entityTypeManager
      ->getStorage('group_type')
      ->load('default');
  }

  /**
   * Tests the maximum ID length of a group type.
   *
   * @covers ::preSave
   * @expectedException \Drupal\Core\Config\Entity\Exception\ConfigEntityIdLengthException
   * @expectedExceptionMessageRegExp /Attempt to create a group type with an ID longer than \d+ characters: \w+\./
   */
  public function testMaximumIdLength() {
    $this->entityTypeManager
      ->getStorage('group_type')
      ->create([
        'id' => $this->randomMachineName(GroupTypeInterface::ID_MAX_LENGTH + 1),
        'label' => 'Invalid ID length group type',
        'description' => '',
      ])
      ->save();
  }

  /**
   * Tests the retrieval of the collection of installed plugins.
   *
   * @covers ::getInstalledContentPlugins
   */
  public function testGetInstalledContentPlugins() {
    $plugins = $this->groupType->getInstalledContentPlugins();
    $this->assertInstanceOf('\Drupal\group\Plugin\GroupContentEnablerCollection', $plugins, 'Loaded the installed plugin collection.');
    $this->assertCount(1, $plugins, 'Plugin collection has one plugin instance.');
  }

  /**
   * Tests whether a group type can tell if it has a plugin installed.
   *
   * @covers ::hasContentPlugin
   */
  public function testHasContentPlugin() {
    $this->assertTrue($this->groupType->hasContentPlugin('group_membership'), 'Found the group_membership plugin.');
    $this->assertFalse($this->groupType->hasContentPlugin('fake_plugin_id'), 'Could not find the fake_plugin_id plugin.');
  }

  /**
   * Tests the retrieval of an installed plugin.
   *
   * @covers ::getContentPlugin
   */
  public function testGetInstalledContentPlugin() {
    $plugin = $this->groupType->getContentPlugin('group_membership');
    $this->assertInstanceOf('\Drupal\group\Plugin\GroupContentEnablerInterface', $plugin, 'Loaded the group_membership plugin.');
  }

  /**
   * Tests the retrieval of a non-existent plugin.
   *
   * @covers ::getContentPlugin
   * @expectedException \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @expectedExceptionMessage Plugin ID 'fake_plugin_id' was not found.
   */
  public function testGetNonExistentContentPlugin() {
    $this->groupType->getContentPlugin('fake_plugin_id');
  }

}
