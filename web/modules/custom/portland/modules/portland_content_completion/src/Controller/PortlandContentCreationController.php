<?php

namespace Drupal\portland_content_controller\Controller;

use Drupal\Core\Controller\ControllerBase;
// use Drupal\Core\Entity\EntityFormBuilderInterface;
// use Drupal\Core\Entity\EntityTypeManagerInterface;
// use Drupal\Core\Render\RendererInterface;
// use Drupal\group\Entity\Controller\GroupContentController;
// use Drupal\group\Entity\GroupInterface;
// use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
// use Drupal\user\PrivateTempStoreFactory;
// use Symfony\Component\DependencyInjection\ContainerInterface;

// use Drupal\Core\Config\Entity;
// use Symfony\Component\HttpKernel;
// use Drupal\Core\Plugin;

/**
 * Creates a Content Completion admin report page
 */
class PortlandContentCreationController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function myPage() {
    $element = array(
      '#markup' => 'Hello, world',
    );
    return $element;
  }

}