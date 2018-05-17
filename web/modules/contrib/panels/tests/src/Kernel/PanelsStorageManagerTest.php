<?php

namespace Drupal\Tests\panels\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\panels\PanelsEvents;
use Drupal\panels\PanelsVariantEvent;

/**
 * @coversDefaultClass \Drupal\panels\Storage\PanelsStorageManager
 * @group Panels
 */
class PanelsStorageManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ctools', 'layout_discovery', 'page_manager', 'panels', 'user'];

  /**
   * Tests that events are fired by the storage manager.
   */
  public function testEvents() {
    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher */
    $event_dispatcher = $this->container->get('event_dispatcher');

    $event_dispatcher->addListener(PanelsEvents::VARIANT_PRE_SAVE, function (PanelsVariantEvent $event) {
      $event->getVariant()->setPageTitle('Gentlefolk, BEHOLD!');
    });
    $event_dispatcher->addListener(PanelsEvents::VARIANT_POST_SAVE, function (PanelsVariantEvent $event) {
      $event->getVariant()->setPageTitle('This will be discarded.');
    });

    $page = Page::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
      'path' => '/' . $this->randomMachineName(),
    ]);
    $page->save();

    $variant = PageVariant::create([
      'id' => 'stunning',
      'label' => $this->randomMachineName(),
      'variant' => 'panels_variant',
      'variant_settings' => [
        'page_title' => 'Pastafazoul',
        'storage_type' => 'page_manager',
        'storage_id' => $this->randomMachineName(),
        'layout' => 'layout_onecol',
        'layout_settings' => [],
      ],
      'page' => $page->id(),
    ]);
    $variant->save();

    $this->container->get('panels.storage_manager')->save($variant->getVariantPlugin());

    // The page title set by the pre-save event handler should be persisted;
    // the one set by the post-save handler should be discarded.
    $page_title = PageVariant::load('stunning')->getVariantPlugin()->getPageTitle();
    $this->assertSame('Gentlefolk, BEHOLD!', $page_title);
  }

}
