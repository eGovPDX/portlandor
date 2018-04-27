<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the group content edit forms.
 *
 * @ingroup group
 */
class GroupContentForm extends ContentEntityForm {

  /**
   * The private store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * Constructs a GroupContentForm object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The private store factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $entity_manager) {
    $this->privateTempStoreFactory = $temp_store_factory;
    parent::__construct($entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * Returns the plugin responsible for this piece of group content.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface
   *   The responsible group content enabler plugin.
   */
  protected function getContentPlugin() {
    /** @var \Drupal\group\Entity\GroupContent $group_content */
    $group_content = $this->getEntity();
    return $group_content->getContentPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Do not allow to edit the group content subject through the UI. Also hide
    // the field when we are on step 2 of a creation wizard.
    if ($this->operation !== 'add' || $form_state->get('group_wizard')) {
      $form['entity_id']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // If we are on step 2 of a wizard, we need to alter the actions.
    if ($form_state->get('group_wizard')) {
      $wizard_id = $form_state->get('group_wizard_id');
      $store = $this->privateTempStoreFactory->get($wizard_id);
      $store_id = $form_state->get('store_id');

      if ($store->get("$store_id:step") === 2) {
        // Add a back button to return to step 1 with.
        $actions['back'] = [
          '#type' => 'submit',
          '#value' => $this->t('Back'),
          '#submit' => ['::back'],
          '#limit_validation_errors' => [],
        ];

        // Make the label of the save button more intuitive.
        if ($wizard_id == 'group_creator') {
          $actions['submit']['#value'] = $this->t('Save group and membership');
        }
        elseif ($wizard_id == 'group_entity') {
          $entity_type_id = $store->get("$store_id:entity")->getEntityTypeId();
          $entity_type = $this->entityManager->getDefinition($entity_type_id);
          $replace = [
            '@entity_type' => $entity_type->getLowercaseLabel(),
            '@group' => $this->getEntity()->getGroup()->label(),
          ];
          $actions['submit']['#value'] = $this->t('Create @entity_type in @group', $replace);
        }

        // Make sure we complete the wizard before saving the group content.
        $index = array_search('::save', $actions['submit']['#submit']);
        array_splice($actions['submit']['#submit'], $index, 0, '::complete');
      }
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
    $group_content = $this->getEntity();

    // The below redirect ensures the user will be redirected to something they
    // can view in the following order: The relationship entity (group content),
    // they target entity itself, the group and finally the front page. This
    // only applies if there was no destination GET parameter set in the URL.
    if ($group_content->access('view')) {
      $form_state->setRedirectUrl($group_content->toUrl());
    }
    elseif ($group_content->getEntity()->access('view')) {
      $form_state->setRedirectUrl($group_content->getEntity()->toUrl());
    }
    elseif ($group_content->getGroup()->access('view')) {
      $form_state->setRedirectUrl($group_content->getGroup()->toUrl());
    }
    else {
      $form_state->setRedirect('<front>');
    }

    return $return;
  }

  /**
   * Goes back to step 1 of the creation wizard.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\group\Entity\Controller\GroupContentController::createForm()
   */
  public function back(array &$form, FormStateInterface $form_state) {
    $store = $this->privateTempStoreFactory->get($form_state->get('group_wizard_id'));
    $store_id = $form_state->get('store_id');
    $store->set("$store_id:step", 1);

    // Disable any URL-based redirect when going back to the previous step.
    $request = $this->getRequest();
    $form_state->setRedirect('<current>', [], ['query' => $request->query->all()]);
    $request->query->remove('destination');
  }

  /**
   * Completes the creation wizard by saving the target entity.
   *
   * Please note that we are instantiating an entity form to replicate the first
   * step and call the save method on that form. This is done to ensure that any
   * logic in the save handler is actually run when the wizard completes.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\group\Entity\Controller\GroupContentController::createForm()
   */
  public function complete(array &$form, FormStateInterface $form_state) {
    $wizard_id = $form_state->get('group_wizard_id');
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $form_state->get('store_id');
    $entity = $store->get("$store_id:entity");

    // Use the add form handler, if available, otherwise default.
    $operation = 'default';
    if ($entity->getEntityType()->getFormClass('add')) {
      $operation = 'add';
    }

    // Replicate the form from step 1 and call the save method.
    $form_object = $this->entityManager->getFormObject($entity->getEntityTypeId(), $operation);
    $form_object->setEntity($entity);
    $form_object->save($form, $form_state);

    // Add the newly saved entity's ID to the group content entity.
    $property = $wizard_id == 'group_creator' ? 'gid' : 'entity_id';
    $this->entity->set($property, $entity->id());

    // We also clear the temp store so we can start fresh next time around.
    $store->delete("$store_id:step");
    $store->delete("$store_id:entity");
  }

}
