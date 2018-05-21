<?php

namespace Drupal\ds\Plugin\DsField\Comment;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ds\Plugin\DsField\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin that renders a view mode.
 *
 * @DsField(
 *   id = "comment_user",
 *   title = @Translation("User"),
 *   entity_type = "comment",
 *   provider = "user"
 * )
 */
class CommentUser extends Entity {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_display_repository);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $view_mode = $this->getEntityViewMode();

    /* @var $comment \Drupal\comment\CommentInterface */
    $comment = $this->entity();
    $uid = $comment->getOwnerId();
    $user = $this->entityTypeManager
      ->getStorage('user')
      ->load($uid);
    $build = $this->entityTypeManager
      ->getViewBuilder('user')
      ->view($user, $view_mode);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function linkedEntity() {
    return 'user';
  }

}
