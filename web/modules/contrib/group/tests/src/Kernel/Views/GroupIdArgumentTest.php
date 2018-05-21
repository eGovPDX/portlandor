<?php

namespace Drupal\Tests\group\Kernel\Views;

use Drupal\group\Entity\Group;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests the group_id argument handler.
 *
 * @see \Drupal\group\Plugin\views\argument\GroupId
 *
 * @group group
 */
class GroupIdArgumentTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group', 'field', 'text', 'group_test_config', 'user', 'group_test_views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_id_argument'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installConfig(['group', 'field', 'group_test_config']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_content');
    $this->installEntitySchema('group_content_type');

    // Set the current user so group creation can rely on it.
    $account = User::create(['name' => $this->randomString()]);
    $account->save();
    $this->container->get('current_user')->setAccount($account);

    ViewTestData::createTestViews(get_class($this), ['group_test_views']);
  }

  /**
   * Tests the group_id argument.
   */
  public function testGroupIdArgument() {
    $view = Views::getView('test_group_id_argument');
    $view->setDisplay();

    /* @var \Drupal\group\Entity\GroupInterface $group1 */
    $group1 = Group::create([
      'type' => 'default',
      'label' => $this->randomMachineName(),
    ]);
    $group1->save();

    /* @var \Drupal\group\Entity\GroupInterface $group2 */
    $group2 = Group::create([
      'type' => 'default',
      'label' => $this->randomMachineName(),
    ]);
    $group2->save();

    $view->preview();
    $this->assertEquals(2, count($view->result), 'Found the expected number of results.');

    // Set the second group id as an argument.
    $view->destroy();
    $view->preview('default', [$group2->id()]);

    // Verify that the title is overridden.
    $this->assertEquals($group2->label(), $view->getTitle());

    // Verify that the argument filtering works.
    $this->assertEquals(1, count($view->result), 'Found the expected number of results.');
    $this->assertEquals((string) $view->style_plugin->getField(0, 'id'), $group2->id(), 'Found the correct group id.');

    // Verify that setting a non-existing id as argument results in no groups
    // being shown.
    $view->destroy();
    $view->preview('default', [22]);
    $this->assertEquals(0, count($view->result), 'Found the expected number of results.');
  }

}
