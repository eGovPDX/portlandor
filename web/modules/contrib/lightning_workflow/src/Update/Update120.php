<?php

namespace Drupal\lightning_workflow\Update;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Update("1.2.0")
 */
final class Update120 implements ContainerInjectionInterface, ContainerAwareInterface {

  use ContainerAwareTrait, StringTranslationTrait;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Update100 constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   (optional) The string translation service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TranslationInterface $translation = NULL) {
    $this->moduleHandler = $module_handler;
    if ($translation) {
      $this->setStringTranslation($translation);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('string_translation')
    );
  }

  /**
   * Grants the reviewer content role permissions to view unpublished content.
   *
   * @param StyleInterface $io
   *   The I/O style.
   *
   * @update
   */
  public function grantUnpublishedContentReviewerAccess(StyleInterface $io) {
    if (! $this->moduleHandler->moduleExists('lightning_roles')) {
      return;
    }

    /** @var \Drupal\lightning_roles\ContentRoleManager $role_manager */
    $role_manager = $this->container->get('lightning.content_roles');

    $question = (string) $this->t('Do you want to give content reviewers the ability to view unpublished content?');
    if ($io->confirm($question)) {
      $role_manager->grantPermissions('reviewer', ['view any unpublished content']);
    }

    $question = (string) $this->t('Do you want to give content reviewers the ability to view the latest unpublished revisions of content?');
    if ($io->confirm($question)) {
      $role_manager->grantPermissions('reviewer', ['view latest version']);
    }
  }

}
