<?php

/**
 * Created by PhpStorm.
 * User: Steven
 * Date: 2019-05-18
 * Time: 10:05 AM
 */

namespace Drupal\portland_zendesk\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\portland_zendesk\Client\ZendeskClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\portland_zendesk\Utils\Utility;
use Drupal\Core\File\FileSystemInterface;

/**
 * Form submission to Zendesk handler.
 *
 * @WebformHandler(
 *   id = "zendesk",
 *   label = @Translation("Zendesk new request"),
 *   category = @Translation("Zendesk"),
 *   description = @Translation("Sends a form submission to Zendesk to create a support request. This handler must fire after any other validation handlers, and it should not be used for forms that allow users to update their original submission. Updating will create a new Zendesk request, which is most likely not the desired behavior."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 *
 * Took inspiration from the following packages:
 * - incomplete Zendesk webform handler port from Drupal 7: https://git.drupalcode.org/sandbox/hanoii-2910895
 * - package synchronizing Drupal forms and Zendesk forms: https://git.drupalcode.org/project/zendesk_tickets
 *
 */
class ZendeskHandler extends WebformHandlerBase
{
  private const ANONYMOUS_EMAIL = 'anonymous@portlandoregon.gov';
  private const JSON_FORM_DATA_FIELD_ID = 17698062540823;

  /**
   * The webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $element_manager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language_manager;

  /**
   * The webform token manager.
   *
   * @var WebformTokenManagerInterface $token_manager
   */
  protected $token_manager;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    $static = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $static->element_manager = $container->get('plugin.manager.webform.element');
    $static->language_manager = $container->get('language_manager');
    $static->token_manager = $container->get('webform.token_manager');
    $static->transliteration = $container->get('transliteration');

    return $static;
  }

  /**
   * Provides list of setting fields
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
      'requester_name' => '',
      'requester_email' => '',
      'subject' => '',
      'comment' => '[webform_submission:values]',
      'tags' => 'drupal webform',
      'priority' => 'normal',
      'status' => 'new',
      'recipient' => '',
      'group_id' => '',
      'assignee_id' => '',
      'type' => 'question',
      'is_child_incident' => '',
      'collaborators' => '',
      'custom_fields' => '',
      'ticket_id_field' => '',
      'parent_ticket_id_field' => '',
      'ticket_fork_field' => '',
      'ticket_form_id' => '',
    ];
  }

  /**
   * @return array
   */
  public function defaultConfigurationNames()
  {
    return array_keys($this->defaultConfiguration());
  }

  /**
   * Provide settings fields for configuration
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $webform_fields = $this->getWebform()->getElementsDecoded();
    $zendesk_subdomain = \Drupal::config('portland_zendesk.adminsettings')->get('subdomain');
    $options = [
      'email' => [''],
      'name' => [''],
      'hidden' => [''],
    ];
    $form_ticket_fields = [];
    $form_field_exclusions = [
      'Subject',
      'Description',
      'Status',
      'Type',
      'Priority',
      'Group',
      'Assignee'
    ];

    // get available email fields to use as requester email address
    foreach ($webform_fields as $key => $field) {
      if (Utility::checkIsGroupingField($field)) {
        foreach ($field as $subkey => $subfield) {
          if (!preg_match("/^#/", $subkey) && isset($subfield['#type'])) {
            if (Utility::checkIsEmailField($subfield)) {
              $options['email'][$subkey] = $subfield['#title'];
            } elseif (Utility::checkIsNameField($subfield)) {
              $options['name'][$subkey] = $subfield['#title'];
            } elseif (Utility::checkIsHiddenField($subfield)) {
              $options['hidden'][$subkey] = $subfield['#title'];
            }
          }
        }
      } else {
        if (Utility::checkIsEmailField($field)) {
          $options['email'][$key] = $field['#title'];
        } elseif (Utility::checkIsNameField($field)) {
          $options['name'][$key] = $field['#title'];
        } elseif (Utility::checkIsHiddenField($field)) {
          $options['hidden'][$key] = $field['#title'];
        }
      }
    }

    $assignees = [];
    $groups = [];
    $ticket_forms = [];
    $recipients = [];

    try {
      // Get available groups and assignees from zendesk.
      // NOTE: Typically we don't want to use individual users here, only groups.
      // Individual users shouldn't be stored in config, which has to be deployed,
      // in case there is an urgent change required. However, if tickets are to be
      // creatd as Solved, they need to have an individual assignee. Using the
      // service account would be acceptable and necessary in this case.

      $client = new ZendeskClient();

      // get list of all groups
      $response_groups = $client->groups()->findAll();
      // store found groups
      foreach ($response_groups->groups as $group) {
        $groups[$group->id] = $group->name;
      }
      // order groups by name
      asort($groups);

      // Get list of all admin and agent users to populate assignee field.
      // The users findAll call only returns 100 results, and the zendesk_api_client_php
      // library doesn't have an iterator call for users. Have to iterate manually,
      // which is done in the function getUsersByRole.

      $admin_users = $this->getUsersByRole($client, 'admin');
      $agent_users = $this->getUsersByRole($client, 'agent');
      $users = array_merge($admin_users, $agent_users);

      // store found agents
      foreach ($users as $user) {
        $assignees[$user->id] = $user->name;
      }

      // order agents by name
      asort($assignees);

      // get list of recipeint addresses
      $response_recipients = $client->supportAddresses()->findAll();
      foreach ($response_recipients->recipient_addresses as $recipient) {
        $recipients[$recipient->email] = $recipient->email;
      }
      asort($recipients);

      // get list of ticket fields and assign them to an array by id->title
      $response_fields = $client->ticketFields()->findAll();

      if ($response_fields->ticket_fields) {
        foreach ($response_fields->ticket_fields as $field) {
          // exclude system ticket fields and inactive fields
          if (!in_array($field->title, $form_field_exclusions) && $field->active) {
            $form_ticket_fields[$field->id] = $field->title;
          }
        }
      }

      // order ticket fields by name
      asort($form_ticket_fields);

      // Get all active ticket forms from Zendesk
      $ticket_forms = $client->get("ticket_forms?active=true")->ticket_forms;
    } catch (\Exception $e) {
      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = nl2br(htmlentities($e->getMessage()));

      // Log error message.
      $this->getLogger()->error('Retrieval of groups or assignees for @form webform Zendesk handler failed. @exception: @message. Click to edit @link.', [
        '@exception' => get_class($e),
        '@form' => $this->getWebform()->label(),
        '@message' => $message,
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ]);
    }

    // build form fields

    $form['requester_name'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Requester name'),
      '#description' => $this->t('The name of the user who requested this ticket. Select from available name fields, or specify a name.'),
      '#default_value' => $this->configuration['requester_name'],
      '#options' => $options['name'],
      '#required' => false
    ];

    $form['requester_email'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Requester email address'),
      '#description' => $this->t('The email address of user who requested this ticket. Select from available email fields, or specify an email address.'),
      '#default_value' => $this->configuration['requester_email'],
      '#options' => $options['email'],
      '#required' => true
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t('The value of the subject field for this ticket'),
      '#default_value' => $this->configuration['subject'],
      '#required' => true
    ];

    $form['comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ticket Body'),
      '#description' => $this->t('The initial comment/message of the ticket.'),
      '#default_value' => $this->configuration['comment'],
      '#format' => '',
      '#required' => true
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Ticket Type'),
      '#description' => $this->t('The type of this ticket. Possible values: "problem", "incident", "question" or "task".'),
      '#default_value' => $this->configuration['type'],
      '#options' => [
        'question' => 'Question',
        'incident' => 'Incident',
        'problem' => 'Problem',
        'task' => 'Task'
      ],
      '#required' => false
    ];

    $form['is_child_incident'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This ticket is the child of a Problem ticket.'),
      '#description' => $this->t('Uses the value in the Zendesk Parent Ticket ID field to identify the parent Problem.'),
      '#default_value' => $this->configuration['is_child_incident'] ?? 0
    ];

    // space separated tags
    $form['tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ticket Tags'),
      '#description' => $this->t('The list of tags applied to this ticket.'),
      '#default_value' => $this->configuration['tags'],
      '#multiple' => true,
      '#required' => false
    ];

    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Ticket Priority'),
      '#description' => $this->t('The urgency with which the ticket should be addressed. Possible values: "urgent", "high", "normal", "low".'),
      '#default_value' => $this->configuration['priority'],
      '#options' => [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent'
      ],
      '#required' => false
    ];

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Ticket Status'),
      '#description' => $this->t('The state of the ticket. Possible values: "new", "open", "pending", "hold", "solved", "closed".'),
      '#default_value' => $this->configuration['status'],
      '#options' => [
        'new' => 'New',
        'open' => 'Open',
        'pending' => 'Pending',
        'hold' => 'Hold',
        'solved' => 'Solved',
        'closed' => 'Closed'
      ],
      '#required' => false
    ];

    // prep recipient field
    // if found groups from Zendesk, populate dropdown.
    $form['recipient'] = [
      '#title' => $this->t('Ticket Recipient'),
      '#description' => $this->t('The email address that is the "recipient" of the ticket, and the one from which notifications are sent.'),
      '#default_value' => $this->configuration['recipient'],
      '#required' => false
    ];
    if (!empty($recipients)) {
      $form['recipient']['#type'] = 'select';
      $form['recipient']['#options'] = ['' => '- None -'] + $recipients;
    }

    // prep groups field
    // if found groups from Zendesk, populate dropdown.
    $form['group_id'] = [
      '#title' => $this->t('Ticket Group'),
      '#description' => $this->t('The id of the intended group'),
      '#default_value' => $this->configuration['group_id'],
      '#required' => false
    ];
    if (!empty($groups)) {
      $form['group_id']['#type'] = 'select';
      $form['group_id']['#options'] = ['' => '- None -'] + $groups;
      $form['group_id']['#description'] = $this->t('The group to which the ticket should be assigned. Set either Ticket Group or Ticket Assignee, but not both.');
    }

    // prep assignees field
    // if found assignees from Zendesk, populate dropdown.
    // otherwise provide field to specify assignee ID
    $form['assignee_id'] = [
      '#title' => $this->t('Ticket Assignee'),
      '#description' => $this->t('The id of the intended assignee'),
      '#default_value' => $this->configuration['assignee_id'],
      '#required' => false
    ];
    if (! empty($assignees)) {
      $form['assignee_id']['#type'] = 'select';
      $form['assignee_id']['#options'] = ['' => '- None -'] + $assignees;
      $form['assignee_id']['#description'] = $this->t('The assignee to which the ticket should be assigned. Set either Ticket Group or Ticket Assignee, but not both. Typically tickets created by webforms should not be assigned to individual users, but tickets that are created as Solved must have an individual assignee. In this case, use the Portland.gov Support service account.');
    } else {
      $form['assignee_id']['#type'] = 'textfield';
      $form['assignee_id']['#attribute'] = [
        'type' => 'number'
      ];
    }

    $form['collaborators'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ticket CCs'),
      '#description' => $this->t('Users to add as cc\'s when creating a ticket.'),
      '#default_value' => $this->configuration['collaborators'],
      '#multiple' => true,
      '#required' => false
    ];

    $form['ticket_form_id'] = [
      '#title' => $this->t('Ticket Form'),
      '#default_value' => $this->configuration['ticket_form_id'],
      '#required' => false
    ];
    if (!empty($ticket_forms)) {
      $form['ticket_form_id']['#type'] = 'select';
      $form['ticket_form_id']['#options'] = ['' => '- None -'] + array_column($ticket_forms, 'name', 'id');
      $form['ticket_form_id']['#description'] = $this->t('The form to use on the ticket');
    }

    $form['custom_fields'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Ticket Custom Fields'),
      '#help' => $this->t('Custom form fields for the ticket'),
      '#description' => $this->t(
        "<div id=\"help\">
          To set the value of one or more custom fields in the new Zendesk ticket, in <a href=\"https://learn.getgrav.org/16/advanced/yaml#mappings\" target=\"_blank\">YAML format</a>, specify a list of pairs consisting of IDs and values.
          You may find the custom field ID when viewing the list of <a href=\"https://{$zendesk_subdomain}.zendesk.com/agent/admin/ticket_fields\" target=\"_blank\">Ticket Fields</a> in Zendesk, or by clicking <strong>Field Reference</strong>
          below for a list of available fields. Values may be a plain text string (with tokens), or an array with the second value specifying a field to get marked as distinct in the JSON form data.
          e.g. <code class=\"CodeMirror\">12345678: ['[webform_submission:values:foo]', 'foo']</code></div>"
      ),
      '#default_value' => $this->configuration['custom_fields'],
      '#description_display' => 'before',
      '#weight' => 90,
      '#attributes' => [
        'placeholder' => "124819322: 'my constant value'\n" .
          "382832843: '[webform_submission:values:multi_select:0:checked]'\n" .
          "146455678: ['[webform_submission:values:contact_email]', 'contact_email']"
      ],
      '#required' => false,
      '#more_title' => 'Field Reference',
      '#more' => '<div class="zd-ticket-reference">' . Utility::convertTable($form_ticket_fields) . '</div>',
    ];

    // display link for token variables
    $form['token_link'] = $this->token_manager->buildTreeLink();

    $form['ticket_id_field'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Zendesk Ticket ID Field'),
      '#description' => $this->t('The name of hidden field which will be updated with the created Ticket ID.'),
      '#default_value' => $this->configuration['ticket_id_field'],
      '#options' => $options['hidden'],
      '#required' => false
    ];

    $form['parent_ticket_id_field'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Zendesk Parent Ticket ID Field'),
      '#description' => $this->t('The name of the hidden field which will store the parent ticket ID in a Problem-Incident relationship. This field automatically gets filled with the created Ticket ID unless is_child_incident is true.'),
      '#default_value' => $this->configuration['parent_ticket_id_field'],
      '#options' => $options['hidden'],
      '#required' => false
    ];

    $form['ticket_fork_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zendesk Ticket Fork Field'),
      '#description' => $this->t('If an element machine name is provided, and that element has multiple values, tickets will be forked from it. The resulting ticket IDs will be placed in the field identified by ticket_id_field in a comma delimted list and may require additional processing for use in a resolution form.'),
      '#default_value' => $this->configuration['ticket_fork_field'],
      '#required' => false
    ];

    $form['subject']['#weight'] = -10; // Place first
    $form['comment']['#weight'] = -10;
    $form['requester_name']['#weight'] = -10;
    $form['requester_email']['#weight'] = -10;
    $form['collaborators']['#weight'] = -7; // CCs
    $form['tags']['#weight'] = -5;
    $form['ticket_id_field']['#weight'] = -4;
    $form['parent_ticket_id_field']['#weight'] = -4;
    $form['type']['#weight'] = -3; // Ticket Type
    $form['incident_child_problem']['#weight'] = -2; // Checkbox
    $form['priority']['#weight'] = -1;
    $form['status']['#weight'] = 0;
    $form['recipient']['#weight'] = 1;
    $form['group_id']['#weight'] = 2;
    $form['assignee_id']['#weight'] = 3;
    $form['ticket_form_id']['#weight'] = 4;
    $form['ticket_fork_field']['#weight'] = 5;
    $form['custom_fields']['#weight'] = 6;

    return parent::buildConfigurationForm($form, $form_state);
  }

  protected function getUsersByRole($client, $role)
  {
    $users = [];
    $params = ['role' => $role];
    $response = $client->users()->findAll($params);

    // Add the initial set of users
    $users = array_merge($users, $response->users);

    // Handle pagination
    while ($response->next_page) {
      // Extract the next page number from the next_page URL
      $nextPage = parse_url($response->next_page, PHP_URL_QUERY);
      parse_str($nextPage, $queryParams);
      $params['page'] = $queryParams['page'];

      $response = $client->users()->findAll($params);
      $users = array_merge($users, $response->users);
    }

    return $users;
  }

  /**
   * Saves handler settings to config
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitConfigurationForm($form, $form_state);

    $submission_value = $form_state->getValues();
    foreach ($this->configuration as $key => $value) {
      if (isset($submission_value[$key])) {
        $this->configuration[$key] = $submission_value[$key];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission)
  {
    // the file upload button triggers the validation handler, which is undesired.
    // in order to prevent that, we need to determine the triggering element for the submission.
    // call our validation function only if it's not an upload button. be extra careful about
    // null references here, since we're not sure when/if these elements will be populated.
    // Candiates for checking whether this is an upload submit:
    //    $form_state->getTriggeringElement()['#submit'][0] == "file_managed_file_submit"
    //    $form_state->getTriggeringElement()['#value']->getUntranslatedString() == "Uplooad"
    if ($form_state->getTriggeringElement() && $form_state->getTriggeringElement()['#value'] === "Submit") {
      $this->sendToZendeskAndValidateTicket($form, $form_state);
    }
  }

  /**
   * Submit report to Zendesk API and validate the ticket was successfully created.
   *
   * By submitting to the API during the validate phase, we can interrupt the form submission,
   * prevent the email handlers from firing, and display an error message to the user. Validation
   * in a custom handler is performed after all the built-in webform validation, so this is a
   * safe approach.
   */
  private function sendToZendeskAndValidateTicket(array &$form, FormStateInterface $form_state)
  {
    if (!$form_state->hasAnyErrors()) {
      // comment out the line below to test the error handling
      $ticket_id = $this->sendToZendesk($form, $form_state);
      if (!$ticket_id) {
        // throw error and don't let form submit
        \Drupal::messenger()->addError(t('There was a problem submitting your report to our support system. Please try again in a few minutes. If the error persists, please <a href="/feedback?subject=The page looks broken&feedback=Report could not be submitted to the support ticketing system.">contact us</a>.'));
        $form_state->setErrorByName('');
      }
    }
  }

  public function sendToZendesk(array &$form, FormStateInterface &$form_state)
  {
    // NOTE: This function will run both when a webform is created, and when it's updated, so this handler
    // should only be used on forms that don't allow updating. Otherwise, a new Zendesk ticket will be created
    // on every submit of the form.

    $new_ticket_id = 0;
    $zendesk_ticket_id_field_name = $this->configuration['ticket_id_field'];
    $zendesk_parent_ticket_id_field_name = $this->configuration['parent_ticket_id_field'];
    $is_child = $this->configuration['is_child_incident'];

    // tickets will be forked on the field identified in the config value 'ticket_fork_field'
    $fork_field_name = $this->configuration['ticket_fork_field'];

    // Since we're doing this in the validate phase, instead of postSave, we need to manually generate
    // a webform_submission object from form_state and pull form values from that for the API submission.
    $webform_submission = $form_state->getFormObject()->getEntity();

    // check for a report_ticket_id value in the form state; if a handler previously submitted
    // a ticket, the ID should be available to subsequent handlers.
    $prev_ticket_id = $form_state->getValue($zendesk_ticket_id_field_name);
    if ($prev_ticket_id) {
      $webform_submission->setElementData($zendesk_ticket_id_field_name, $prev_ticket_id);
    }

    // check for a parent_ticket_id value in the form state; if a handler previously submitted
    // a ticket, the ID should be available to subsequent handlers.
    $parent_ticket_id = $form_state->getValue($zendesk_parent_ticket_id_field_name);
    if ($parent_ticket_id) {
      $webform_submission->setElementData($zendesk_parent_ticket_id_field_name, $parent_ticket_id);
    }

    // the 2nd time through, $prev_ticket_id and $parent_ticket_id are both set

    if ($fork_field_name) {
      // if the handler has a fork field configured, grab the values array from that field so we can
      // spin through it and stuff a single value into the webform_submission for each ticket being created.

      $data = $webform_submission->getData();
      $fork_field_array = $data[$fork_field_name];  // TODO: inefficient; improve?
      $ticket_ids = [];

      foreach ($fork_field_array as $key => $value) {
        $data[$fork_field_name] = $fork_field_array[$key];
        $webform_submission->setData($data);
        $configuration = $this->token_manager->replace($this->configuration, $webform_submission);

        // call function to create ticket in Zendesk and store resulting ticket ID
        $ticket_id = $this->submitTicket($webform_submission, $configuration);
        // if ticket ID = 0, there was an error, so exit out and make the form error
        if ($ticket_id === 0) return null;

        $ticket_ids[] = $ticket_id;
      }

      $new_ticket_id = implode(",", $ticket_ids);
    } else {
      $configuration = $this->token_manager->replace($this->configuration, $webform_submission);
      $new_ticket_id = $this->submitTicket($webform_submission, $configuration);
      $data = $webform_submission->getData();
    }

    // if field is set and present, add ticket ID to hidden Zendesk Ticket ID field
    // NOTE: Only do this if $prev_ticket_id isn't already set
    if (!$prev_ticket_id && $zendesk_ticket_id_field_name && array_key_exists($zendesk_ticket_id_field_name, $data) && $new_ticket_id) {
      $data[$zendesk_ticket_id_field_name] = $new_ticket_id;
      $form_state->setValue($zendesk_ticket_id_field_name, $new_ticket_id);
      $form['values'][$zendesk_ticket_id_field_name] = $new_ticket_id;
    }

    // if this is a Problem ticket and parent ticket ID field is present, add new ticket ID there too
    if (!$parent_ticket_id && $zendesk_parent_ticket_id_field_name && array_key_exists($zendesk_parent_ticket_id_field_name, $data) && $new_ticket_id && !$is_child) {
      $data[$zendesk_parent_ticket_id_field_name] = $new_ticket_id;
      $form_state->setValue($zendesk_parent_ticket_id_field_name, $new_ticket_id);
      $form['values'][$zendesk_parent_ticket_id_field_name] = $new_ticket_id;
    }

    return $new_ticket_id; // if a null is returned, an error/try-again message will be displayed to the user
  }

  public function submitTicket(WebformSubmissionInterface $webform_submission, $configuration)
  {
    $zendesk_parent_ticket_id_field_name = $this->configuration['parent_ticket_id_field'];
    $is_child = $this->configuration['is_child_incident'];

    $submission_fields = $webform_submission->toArray(TRUE);
    $new_ticket_id = 0;
    $request = [];

    if ($is_child && array_key_exists('parent_ticket_id', $submission_fields['data'])) {
      $parent_ticket_id = $submission_fields['data'][$zendesk_parent_ticket_id_field_name];
      $request['problem_id'] = $parent_ticket_id;
    }

    // Allow for either values coming from other fields or static/tokens
    foreach ($this->defaultConfigurationNames() as $field) {
      $request[$field] = $configuration[$field];
      if (!empty($submission_fields['data'][$configuration[$field]])) {
        $request[$field] = $submission_fields['data'][$configuration[$field]];
      }
    }

    // clean up tags
    $request['tags'] = Utility::cleanTags($request['tags']);
    $request['collaborators'] = preg_split("/[^a-z0-9_\-@\.']+/i", $request['collaborators']);
    if (!empty($request['ticket_form_id'])) $request['ticket_form_id'] = $this->configuration['ticket_form_id'];

    // restructure requester
    if (!isset($request['requester'])) {
      // if requester email doesn't contain an @, that means the field was empty or the value wasn't set,
      // so default to anonymous.
      if (!str_contains($request['requester_email'], '@')) {
        $request['requester_email'] = self::ANONYMOUS_EMAIL;
      }

      // if requester_name contains an html entity, we need to html decode it
      // so it doesn't get passed to Zendesk as an entity.
      if (str_contains($request['requester_name'], '&')) {
        $request['requester_name'] = html_entity_decode($request['requester_name']);
      }

      $request['requester'] = $request['requester_name']
        ? [
          'name' => Utility::convertName($request['requester_name']),
          'email' => $request['requester_email'],
        ]
        : $request['requester_email'];

      unset($request['requester_name']);
      unset($request['requester_email']);
    }

    // restructure comment array
    if (!isset($request['comment']['body'])) {
      $comment = $request['comment'];
      $request['comment'] = [
        'html_body' => $comment
      ];
    }

    // convert custom fields format from [key:data} to [id:key,value:data] for Zendesk field referencing
    $custom_fields = Yaml::decode($request['custom_fields']);
    unset($request['custom_fields']);
    $request['custom_fields'] = [];
    if ($custom_fields) {
      foreach ($custom_fields as $key => $raw_value) {
        // if value is an array of [token, field_name], extract the token which is what we want.
        // else use the value as the token
        $value = is_array($raw_value) ? $raw_value[0] : $raw_value;

        // KLUGE: this is a kludge to prevent querystring ampersands from being escaped in the resolution_url or location_address custom field,
        // which prevents the URL from being usable in Zendesk emails, since it doesn't unescape them in triggers. For the
        // Portland instance, the resolution_url field should always have this key, but other url custom fields may need
        // to be added in the future.
        if ($key == "6355783758871" || $key == "1500012743961") {
          $value = str_replace("&amp;", "&", $value);
          $value = str_replace("&#039;", '\'', $value);
        } // END KLUGE

        // NEXT KLUGE: If the token is empty, it's not getting replaced, and the token code is appearing in custom fields
        // in Zendesk. also, the default filter isn't working. so we'll just clear out any values that start with a token
        // string. at present, custom values will only include the tokens, not additional string values, so this approach
        // is fine for now but may need to be extended to remove the token code from within a longer string.
        if (is_string($value) && substr($value, 0, 27) == "[webform_submission:values:") {
          $value = "";
        }

        $request['custom_fields'][] = [
          'id' => $key,
          'value' => $value
        ];
      }
    }

    // retrieve the name of the field in which to store the created Zendesk Ticket ID
    $zendesk_ticket_id_field_name = $configuration['ticket_id_field'];

    // an array of [field_name => custom_field_id] for each webform field that has an associated Zendesk custom field
    $webform_fields_with_distinct_zendesk_fields = is_array($custom_fields) ? array_flip(array_map(
      fn($val) => $val[1],
      array_filter($custom_fields, fn($val) => is_array($val) && count($val) === 2),
    )) : [];
    $json_form_data = [];
    $exclude_from_json = $this->webform->getThirdPartySetting('portland', 'exclude_from_json') ?? [];
    foreach ($submission_fields['data'] as $key => $value) {
      // exclude ticket ID from json as it will always be empty at this point
      if ($key === $zendesk_ticket_id_field_name) continue;

      if (array_key_exists($key, $exclude_from_json)) continue;

      // check if composite element
      if (is_array($value) && !array_is_list($value)) {
        foreach ($value as $child_key => $child_value) {
          $composite_key = $key . ':' . $child_key;
          $json_form_data[$composite_key] = [
            'value' => $child_value,
          ];

          if (array_key_exists($composite_key, $webform_fields_with_distinct_zendesk_fields)) {
            $json_form_data[$composite_key]['ticket_field_id'] = $webform_fields_with_distinct_zendesk_fields[$composite_key];
          }
        }
      } else {
        $json_form_data[$key] = [
          'value' => $value,
        ];

        if (array_key_exists($key, $webform_fields_with_distinct_zendesk_fields)) {
          $json_form_data[$key]['ticket_field_id'] = $webform_fields_with_distinct_zendesk_fields[$key];
        }
      }
    }

    $request['custom_fields'][] = [
      'id' => self::JSON_FORM_DATA_FIELD_ID,
      'value' => json_encode($json_form_data),
    ];

    // set external_id to connect zendesk ticket with submission ID
    $request['external_id'] = $webform_submission->id();

    // get list of all webform fields with a file field type
    $file_fields = $this->getWebform()->getElementsManagedFiles();
    // get all webform elements
    $elements = $this->getWebform()->getElementsInitializedAndFlattened();

    $lock = \Drupal::lock();
    $sid  = $webform_submission->id();
    $key  = 'zendesk_send:' . ($sid ?: $webform_submission->uuid());

    if (!$lock->acquire($key, 30)) {
      throw new \RuntimeException('Duplicate submission in progress.');
    }

    // attempt to send request to create zendesk ticket
    $__temp_paths = [];
    try {
      // initiate api client
      $client = new ZendeskClient();

      // Checks for files in submission values and uploads them if found
      foreach ($submission_fields['data'] as $field_key => $field_data) {
        if (in_array($field_key, $file_fields) && !empty($field_data)) {
          $fid_to_element = [];
          $element = $elements[$field_key];
          $element_plugin = $this->element_manager->getElementInstance($element);
          // If forking is enabled off of this field, we can assume it doesn't contain multiple values
          $multiple = $field_key === $this->configuration['ticket_fork_field'] ? false : $element_plugin->hasMultipleValues($element);
          // Get fids from composite sub-elements.
          // Adapted from WebformSubmissionForm::getUploadedManagedFileIds
          if ($element_plugin instanceof \Drupal\webform\Plugin\WebformElement\WebformCompositeBase) {
            $managed_file_keys = $element_plugin->getManagedFiles($element);
            // Convert single composite value to array of multiple composite values.
            $data = $multiple ? $field_data : [$field_data];
            foreach ($data as $item) {
              foreach ($managed_file_keys as $manage_file_key) {
                if (is_array($item) && $item[$manage_file_key]) {
                  $fid_to_element[$item[$manage_file_key]] = $element["#webform_composite_elements"][$manage_file_key] ?? null;
                }
              }
            }
          } else {
            foreach ((array) $field_data as $fid) {
              $fid_to_element[$fid] = $element;
            }
          }

          // individually attach each uploaded file
          foreach ($fid_to_element as $fid => $element) {
            $file = File::load($fid);

            // add uploads key to Zendesk comment, if not already present
            if ($file && !array_key_exists('uploads', $request['comment'])) {
              $request['comment']['uploads'] = [];
            }

            if ($element) $filename = $this->transformFilename($file->getFilename(), $element, $webform_submission);

            $path = $this->pathForZendeskUpload($file);   // new helper below
            $__temp_paths[] = $path;                      // remember to clean up
            $attachment = $client->attachments()->upload([
              'file' => $path,                             // real path, not private://
              'type' => $file->getMimeType(),
              'name' => $filename,
            ]);

            // add upload token to comment
            if ($attachment && isset($attachment->upload->token)) {
              $request['comment']['uploads'][] = $attachment->upload->token;
            }
          }
        }
      }

      // create ticket
      $new_ticket = $client->tickets()->create($request);

      $new_ticket_id = $new_ticket->ticket->id;
    } catch (\Exception $e) {

      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = nl2br(htmlentities($e->getMessage()));

      // Log error message.
      $this->getLogger()->error('@form webform submission to zendesk failed. @exception: @message. Click to edit @link.', [
        '@exception' => get_class($e),
        '@form' => $this->getWebform()->label(),
        '@message' => $message,
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ]);
    } finally {
      // always remove any temporary copies we created
      $this->cleanupTempUploads($__temp_paths);
      $lock->release($key);
    }

    return $new_ticket_id;
  }

  // Add to your handler class:

  /**
   * Resolve a File entity to a real path suitable for Zendesk SDK.
   * If private:// path isn't real yet or contains "/_sid_/", copy to temporary://.
   */
  private function pathForZendeskUpload(\Drupal\file\Entity\File $file): string
  {
    $fs = \Drupal::service('file_system');
    $uri = $file->getFileUri();
    $real = $fs->realpath($uri) ?: '';
    $needs_copy = !$real || !file_exists($real) || str_contains($uri, '/_sid_/');

    if ($needs_copy) {
      $dir = 'temporary://zendesk_uploads';
      $fs->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

      // Create a unique temp name (Drupal wrapper around tempnam()).
      $dest = $fs->tempnam($fs->realpath($dir), 'zd-');
      // Convert it back to a stream wrapper URI so `copy()` can use it.
      // If $dest is a real path, get a matching URI:
      $dest_uri = $fs->realpath($dir) ? $dir . '/' . basename($dest) : $dest;

      $copied = $fs->copy($uri, $dest_uri, FileSystemInterface::EXISTS_REPLACE);
      $real = $fs->realpath($copied) ?: '';
    }

    if (!$real || !file_exists($real)) {
      throw new \RuntimeException(sprintf('Upload source missing for fid=%d (uri=%s)', $file->id(), $uri));
    }
    return $real;
  }

  private function cleanupTempUploads(array $paths): void
  {
    $fs = \Drupal::service('file_system');
    $base = rtrim($fs->realpath('temporary://zendesk_uploads'), DIRECTORY_SEPARATOR);

    foreach ($paths as $p) {
      if (!is_string($p)) {
        continue;
      }
      $real = realpath($p) ?: $p;
      if ($base && $real && str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
        @$fs->unlink($real);
      }
    }
  }

  /**
   * Code mostly adapted from WebformManagedFileBase::getFileDestinationUri.
   *
   * Replace tokens and sanitizes filename according to element settings.
   */
  private function transformFilename(string $filename, array $element, WebformSubmissionInterface $webform_submission)
  {
    $destination_extension = pathinfo($filename, PATHINFO_EXTENSION);
    $destination_basename = substr(pathinfo($filename, PATHINFO_BASENAME), 0, -strlen(".$destination_extension"));

    // Replace tokens in file name.
    if (isset($element['#file_name']) && $element['#file_name']) {
      $destination_basename = $this->token_manager->replace($element['#file_name'], $webform_submission);
    }

    // Sanitize filename.
    // @see http://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
    // @see \Drupal\webform_attachment\Element\WebformAttachmentBase::getFileName
    if (!empty($element['#sanitize'])) {
      $destination_extension = mb_strtolower($destination_extension);

      $destination_basename = mb_strtolower($destination_basename);
      $destination_basename = $this->transliteration->transliterate($destination_basename, $this->language_manager->getCurrentLanguage()->getId(), '-');
      $destination_basename = preg_replace('([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})', '', $destination_basename);
      $destination_basename = preg_replace('/\s+/', '-', $destination_basename);
      $destination_basename = trim($destination_basename, '-');

      // If the basename is empty use the element's key, composite key, or type.
      if (empty($destination_basename)) {
        if (isset($element['#webform_key'])) {
          $destination_basename = $element['#webform_key'];
        } elseif (isset($element['#webform_composite_key'])) {
          $destination_basename = $element['#webform_composite_key'];
        } else {
          $destination_basename = $element['#type'];
        }
      }
    }

    return $destination_basename . '.' . $destination_extension;
  }

  /**
   * Displays a list of configured values on the Handlers page
   * {@inheritdoc}
   */
  public function getSummary()
  {
    $markup = [];
    $configNames = array_keys($this->defaultConfiguration());
    $excluded_fields = ['comment', 'custom_fields'];

    // loop through fields to display an at-a-glance summary of settings
    foreach ($configNames as $configName) {
      if (! in_array($configName, $excluded_fields)) {
        $markup[] = '<strong>' . $this->t($configName) . ': </strong>' . ($this->configuration[$configName]);
      }
    }

    return [
      '#markup' => implode('<br>', $markup),
    ];
  }

  // Deprecated functions

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsNameField(array $field)
  {
    return Utility::checkIsNameField($field);
  }

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsEmailField(array $field)
  {
    return Utility::checkIsEmailField($field);
  }

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsHiddenField(array $field)
  {
    return Utility::checkIsHiddenField($field);
  }

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsGroupingField(array $field)
  {
    return Utility::checkIsGroupingField($field);
  }

  /**
   * @param string $text
   * @return string
   * @deprecated
   */
  protected function cleanTags($text = '')
  {
    return Utility::cleanTags($text);
  }

  /**
   * @param string $text
   * @return string
   * @deprecated
   */
  protected function convertTags($text = '')
  {
    return Utility::convertTags($text);
  }

  /**
   * @param string $text
   * @return string
   * @deprecated
   */
  protected function convertName($name_parts)
  {
    return Utility::convertName($name_parts);
  }

  /**
   * @param array $text
   * @return string
   * @deprecated
   */
  protected function convertTable($set)
  {
    return Utility::convertTable($set);
  }
}
