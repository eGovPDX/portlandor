<?php

namespace Drupal\masquerade\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\masquerade\Masquerade;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Masquerade' block.
 *
 * @Block(
 *   id = "masquerade",
 *   admin_label = @Translation("Masquerade"),
 *   category = @Translation("Forms"),
 * )
 */
class MasqueradeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * Constructs a new MasqueradeBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\masquerade\Masquerade $masquerade
   *   The masquerade service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, Masquerade $masquerade) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $form_builder;
    $this->masquerade = $masquerade;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('masquerade')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->masquerade->isMasquerading()) {
      return AccessResult::forbidden()->addCacheContexts(['session.is_masquerading']);
    }
    // Display block for all users that has any of masquerade permissions.
    return AccessResult::allowedIfHasPermissions($account, $this->masquerade->getPermissions(), 'OR')
      ->addCacheContexts(['session.is_masquerading']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['session.is_masquerading']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder->getForm('Drupal\masquerade\Form\MasqueradeForm');
  }

}
