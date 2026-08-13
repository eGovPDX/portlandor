<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows non-admin users to add specified sitewide roles to a user. Available roles are selected in the VBO preconfiguration form.
 */
#[Action(
  id: 'portland_add_user_roles',
  label: new TranslatableMarkup('Add sitewide roles to a user (custom action)'),
  type: 'user',
)]
class AddUserRolesAction extends ViewsBulkOperationsActionBase implements PluginFormInterface, ContainerFactoryPluginInterface {
  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(?UserInterface $user = NULL) {
    if (empty($user)) {
      return $this->t('No user specified.');
    }

    $roles = $this->configuration['roles'];
    foreach ($roles as $role) {
      if (!empty($role)) {
        $user->addRole($role);
      }
    }
    $user->save();

    return $this->t('Added roles to the user.');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array
  {
    return [
      'roles' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $roles = array_column($this->entityTypeManager->getStorage('user_role')->loadMultiple(), 'label', 'id');
    $available_role_ids = $this->context['preconfiguration']['available_roles'];
    $available_roles = array_intersect_key($roles, array_flip($available_role_ids));
    $form['roles'] = [
      '#title' => $this->t('Select the role(s) to add to these users'),
      '#type' => 'checkboxes',
      '#options' => $available_roles ?? [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setValue('roles', array_filter($form_state->getValue('roles')));
    parent::submitConfigurationForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $roles = array_filter($form_state->getValue('roles'));
    if (empty($roles)) {
      $form_state->setErrorByName('roles', $this->t('Please select at least one role.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
    $roles = array_filter(
      $this->entityTypeManager->getStorage('user_role')->loadMultiple(),
      fn(RoleInterface $role) => $role->id() !== 'anonymous' && $role->id() !== 'authenticated' && !$role->isAdmin(),
    );
    $form['available_roles'] = [
      '#type' => 'select',
      '#title' => $this->t('Available role(s) for user to select'),
      '#options' => array_column($roles, 'label', 'id'),
      '#multiple' => true,
      '#default_value' => $values['available_roles'] ?? [],
      '#description' => $this->t('Only select roles that those with access to this view should have access to add to other users.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof UserInterface) {
      /** @var \Drupal\user\UserInterface $object */
      // Don't allow non-admins to edit admin users.
      return (!$account->hasRole('administrator') && !$object->hasRole('administrator')) ? AccessResult::forbidden() : AccessResult::allowed();
    }

    return $return_as_object ? AccessResult::forbidden() : FALSE;
  }
}
