<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Url;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for group type forms.
 */
class GroupTypeForm extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    $form = parent::form($form, $form_state);
    $type = $this->entity;

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $type->label(),
      '#description' => $this->t('The human-readable name of this group type. This text will be displayed as part of the list on the %group-add page. This name must be unique.', [
        '%group-add' => $this->t('Add group'),
      ]),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => GroupTypeInterface::ID_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\group\Entity\GroupType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this group type. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %group-add page, in which underscores will be converted into hyphens.', [
        '%group-add' => $this->t('Add group'),
      ]),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->getDescription(),
      '#description' => $this->t('This text will be displayed on the <em>Add group</em> page.'),
    ];

    $form['creator_membership'] = [
      '#title' => $this->t('The group creator automatically becomes a member'),
      '#type' => 'checkbox',
      '#default_value' => $type->creatorGetsMembership(),
      '#description' => $this->t('This will make sure that anyone who creates a group of this type will automatically become a member of it.'),
    ];

    $form['creator_wizard'] = [
      '#title' => $this->t('Group creator must complete their membership'),
      '#type' => 'checkbox',
      '#default_value' => $type->creatorMustCompleteMembership(),
      '#description' => $this->t('This will first show you the form to create the group and then a form to fill out your membership details.<br />You can choose to disable this wizard if you did not or will not add any fields to the membership.<br /><strong>Warning:</strong> If you do have fields on the membership and do not use the wizard, you may end up with required fields not being filled out.'),
      '#states' => [
        'visible' => [':input[name="creator_membership"]' => ['checked' => TRUE]],
      ],
    ];

    // Add-form specific elements.
    if ($this->operation == 'add') {
      $form['add_admin_role'] = [
        '#title' => $this->t('Automatically configure an administrative role'),
        '#type' => 'checkbox',
        '#default_value' => 0,
        '#description' => $this->t("This will create an 'Admin' role by default which will have all currently defined permissions."),
      ];

      $form['assign_admin_role'] = [
        '#title' => $this->t('Automatically assign this administrative role to group creators'),
        '#type' => 'checkbox',
        '#default_value' => 0,
        '#description' => $this->t("This will assign the 'Admin' role to the group creator membership."),
        '#states' => [
          'visible' => [
            ':input[name="creator_membership"]' => ['checked' => TRUE],
            ':input[name="add_admin_role"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
    // Edit-form specific elements.
    else {
      $options = [];
      foreach ($type->getRoles(FALSE) as $group_role) {
        $options[$group_role->id()] = $group_role->label();
      }

      $form['creator_roles'] = [
        '#title' => $this->t('Group creator roles'),
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => $type->getCreatorRoleIds(),
        '#description' => $this->t('Please select which custom group roles a group creator will receive.'),
        '#states' => [
          'visible' => [':input[name="creator_membership"]' => ['checked' => TRUE]],
        ],
      ];

      if (empty($options)) {
        $add_role_url = Url::fromRoute('entity.group_role.add_form', ['group_type' => $type->id()]);
        $t_args = ['@url' => $add_role_url->toString()];
        $description = $this->t('You do not have any custom group roles yet, <a href="@url">create one here</a>.', $t_args);
        $form['creator_roles']['#description'] .= "<br /><em>$description</em>";
      }
    }

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save group type');
    $actions['delete']['#value'] = $this->t('Delete group type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we might check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupTypeInterface $type */
    $type = $this->entity;

    // Trim any whitespace off the label.
    $type->set('label', trim($type->label()));

    // Clean up the creator role IDs as it comes from a checkboxes element.
    if ($creator_roles = $type->getCreatorRoleIds()) {
      $type->set('creator_roles', array_values(array_filter($creator_roles)));
    }

    $status = $type->save();
    $t_args = ['%label' => $type->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The group type %label has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The group type %label has been added. You may now configure which roles a group creator will receive by editing the group type.', $t_args));
      $context = array_merge($t_args, ['link' => $type->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('group')->notice('Added group type %label.', $context);

      // Optionally create a default admin role.
      if ($form_state->getValue('add_admin_role')) {
        $storage = $this->entityTypeManager->getStorage('group_role');

        /** @var \Drupal\group\Entity\GroupRoleInterface $group_role */
        $group_role = $storage->create([
          'id' => $type->id() . '-admin',
          'label' => $this->t('Admin'),
          'weight' => 100,
          'group_type' => $type->id(),
        ]);
        $group_role->grantAllPermissions()->save();

        // Optionally auto-assign the admin role to group creators.
        if ($form_state->getValue('assign_admin_role')) {
          $type->set('creator_roles', [$type->id() . '-admin'])->save();
        }
      }
    }

    $form_state->setRedirectUrl($type->toUrl('collection'));
  }

}
