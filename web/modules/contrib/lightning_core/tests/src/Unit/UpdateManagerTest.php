<?php

namespace Drupal\Tests\lightning_core\Unit;

use Drupal\lightning_core\UpdateManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * @coversDefaultClass \Drupal\lightning_core\UpdateManager
 *
 * @group lightning
 * @group lightning_core
 */
class UpdateManagerTest extends UnitTestCase {

  /**
   * @covers ::toSemanticVersion
   *
   * @dataProvider providerSemanticVersion
   */
  public function testSemanticVersion($drupal_version, $semantic_version) {
    $this->assertSame($semantic_version, UpdateManager::toSemanticVersion($drupal_version));
  }

  public function providerSemanticVersion() {
    return [
      ['8.x-1.12', '1.12.0'],
      ['8.x-1.2-alpha3', '1.2.0-alpha3'],
      ['8.x-2.7-beta3', '2.7.0-beta3'],
      ['8.x-1.42-rc1', '1.42.0-rc1'],
      ['8.x-1.x-dev', '1.x-dev'],
      // This is a weird edge case only used by the Lightning profile.
      ['8.x-3.001', '3.001.0'],
    ];
  }

  /**
   * @covers ::getTasks
   */
  public function testGetTasks() {
    $update_manager = new TestUpdateManager(
      new \ArrayIterator,
      $this->createMock('\Drupal\Core\DependencyInjection\ClassResolverInterface'),
      $this->createMock('\Drupal\Core\Config\ConfigFactoryInterface')
    );

    $tasks = $update_manager->getTasks(new TestUpdateHandler);
    $tasks = iterator_to_array($tasks);
    $this->assertCount(2, $tasks);

    $io = $this->prophesize(StyleInterface::class);

    $io->confirm('Can you trip like I do?')->willReturn(TRUE);
    $io->success('Dude, sweet!')->shouldBeCalled();
    $tasks[0]->execute($io->reveal());

    $io->confirm('Why would you do this?')->willReturn(FALSE);
    $io->error('Oh, the humanity!')->shouldNotBeCalled();
    $tasks[1]->execute($io->reveal());
  }

}

/**
 * Exposes protected UpdateManager methods for testing.
 */
final class TestUpdateManager extends UpdateManager {

  public function getTasks($handler) {
    return parent::getTasks($handler);
  }

}

/**
 * A test class containing discoverable updates.
 */
final class TestUpdateHandler {

  /**
   * @update
   *
   * @ask Can you trip like I do?
   */
  public function foo(StyleInterface $io) {
    $io->success('Dude, sweet!');
  }

  /**
   * @update
   *
   * @ask Why would you do this?
   */
  public function nope(StyleInterface $io) {
    $io->error('Oh, the humanity!');
  }

  public function bar() {
  }

  /**
   * @update
   */
  protected function baz() {
    // Protected methods should not be discovered, even if they have the
    // @update annotation.
  }

  /**
   * @update
   */
  private function wambooli() {
    // As with protected methods, private methods should never be discovered
    // even if they have the @update annotation.
  }

}
