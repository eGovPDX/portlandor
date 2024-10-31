<?php

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


/**
 * Form submission to Zendesk to update a ticket.
 *
 * @WebformHandler(
 *   id = "zendesk_update_ticket",
 *   label = @Translation("Zendesk update request"),
 *   category = @Translation("Zendesk"),
 *   description = @Translation("Updates an existing Zendesk support request."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 *
 * The handler also validates that the right ticket is being updated. Each ticket includes the original report webform UUID.
 * The UUID is passed into a hidden field in the resolution form when the company agent click the link in their notification email.
 * Before updating the ticket, the updte handler queries the ticket identified by the ticket_id field and verifies that the
 * UUID is a match. This prevents a company agent from somehow changing the ticket ID value in the link URL, or a malicious actor
 * from getting ahold of the link and updating multiple unrelated tickets by changing the ticket ID value and resubmitting.
 *
 * When using this handler to update an "interaction ticket" created by a 311 agent, which uses the zendesk_request_number
 * sub-element of the Support Agent Widget, it needs to be accessed a little differently from the data array since it's nested.
 * Logic has been added to this handler to look for zendesk_request_number under the support_agent_use_only element. Note that
 * this approach requires that the widget always be named support_agent_use_only.
 *
 */
class ZendeskUpdateHandler extends WebformHandlerBase
{

  /**
   * @var WebformTokenManagerInterface $token_manager
   */
  protected $token_manager;

  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {

    /**
     * @var WebformTokenManagerInterface $webform_token_manager
     */

    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $webform_token_manager = $container->get('webform.token_manager');
    $instance->setTokenManager($webform_token_manager);
    $instance->fileSystem = $container->get('file_system');

    return $instance;
  }

  /**
   * Provides list of setting fields
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
      'comment' => '',
      'comment_private' => false,
      'tags' => '',
      'priority' => '',
      'status' => '',
      'group_id' => '',
      'assignee_id' => '',
      'type' => '',
      'collaborators' => '',
      'custom_fields' => '',
      'ticket_id_field' => '',
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
    $form_ticket_fields = [];
    $form_field_exclusions = [
      'Subject',
      'Description',
      'Status',
      'Type',
      'Priority',
      'Group',
      'Assignee',
    ];

    $assignees = [];
    $groups = [];
    $ticket_forms = [];

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
    $form['intro_text'] = [
      '#type' => 'markup',
      '#title' => $this->t('How to use this handler'),
      '#markup' => '<p>This handler will update an existing ticket identified by Zendesk Ticket ID Field, which must be provided in the webform either by the user or prepopulated from the URL. Only fields with values will be updated. <b>Leave fields empty if they should not be updated.</b></p>',
    ];

    $form['ticket_id_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zendesk Ticket ID Field'),
      '#description' => $this->t('Enter the machine name of the field that contains the Zendesk ticket ID.'),
      '#default_value' => $this->configuration['ticket_id_field'],
      '#required' => true
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Ticket Type'),
      '#description' => $this->t('The type of this ticket. Possible values: "problem", "incident", "question" or "task".'),
      '#default_value' => $this->configuration['type'],
      '#options' => [
        '' => 'Do not update',
        'question' => 'Question',
        'incident' => 'Incident',
        'problem' => 'Problem',
        'task' => 'Task'
      ],
      '#required' => false
    ];

    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Ticket Priority'),
      '#description' => $this->t('The urgency with which the ticket should be addressed. Possible values: "urgent", "high", "normal", "low".'),
      '#default_value' => $this->configuration['priority'],
      '#options' => [
        '' => 'Do not update',
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
        '' => 'Do not update',
        'new' => 'New',
        'open' => 'Open',
        'pending' => 'Pending',
        'hold' => 'Hold',
        'solved' => 'Solved',
        'closed' => 'Closed'
      ],
      '#required' => false
    ];

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
      $form['group_id']['#options'] = ['' => '- None/No Change -'] + $groups;
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
      $form['assignee_id']['#type'] = 'webform_select_other';
      $form['assignee_id']['#options'] = ['' => '- None/No Change -'] + $assignees;
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

    $form['comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ticket Comment'),
      '#description' => $this->t('Use this field to add a comment to every ticket updated by this webform. A comment notification will be sent to the original requester.'),
      '#default_value' => $this->configuration['comment'],
      '#format' => 'full_html'
    ];

    $form['comment_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Private Comment'),
      '#description' => $this->t('Check this box if you want the ticket comment to be private and not visible to the requester.'),
      '#default_value' => $this->configuration['comment_private']
    ];

    $form['custom_fields'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Ticket Custom Fields'),
      '#help' => $this->t('Custom form fields for the ticket'),
      '#description' => $this->t(
        '<div id="help">To set the value of one or more custom fields in the new Zendesk ticket, in <a href="https://learn.getgrav.org/16/advanced/yaml#mappings" target="_blank">YAML format</a>, specify a list of pairs consisting of IDs and values. You may find the custom field ID when viewing the list of <a href="https://' . $zendesk_subdomain . '.zendesk.com/agent/admin/ticket_fields" target="_blank">Ticket Fields</a> in Zendesk, or by clicking "<strong>Field Reference</strong>" below for a list of available fields. Values may be plain text, or Drupal webform tokens/placeholders. <p class="">Eg. <code class="CodeMirror"><span>12345678</span>: <span>\'foobar\'</span></code></p> </div>'
      ),
      '#default_value' => $this->configuration['custom_fields'],
      '#description_display' => 'before',
      '#attributes' => [
        'placeholder' => '146455678: \'[webform_submission:values:email]\''
      ],
      '#required' => false,
      '#more_title' => 'Field Reference',
      '#more' => '<div class="zd-ticket-reference">' . Utility::convertTable($form_ticket_fields) . '</div>',
    ];

    // display link for token variables
    $form['token_link'] = $this->getTokenManager()->buildTreeLink();

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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission)
  {
    parent::submitForm($form, $form_state, $webform_submission); // TODO: Change the autogenerated stub

    /*
      * -------------------------------------------------------------------------------------------------------------
      * Request format:
      *
    $request = [
        'subject' => 'test 1 ticket',
        'requester' => [
            'email' => 'scsisland@gmail.com'
        ],
        'comment' => [
            'body' => 'this is a test tickets'
        ],
        'tags' => [],
        'priority' => 'low',
        'status' => 'new',
        'collaborators' => [],
    ];
    * --------------------------------------------------------------------------------------------------------------
    */
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

      // // does it help to put the report_ticket_id in the user input? will that get it to
      // // be used in token replacement in the 2nd handler? if not, we may need to do some
      // // manual kerjiggering.
      // $user_input = $form_state->getUserInput();
      // if (array_key_exists('report_ticket_id', $user_input)) {
      //   $user_input['report_ticket_id'] = $form_state->getValue('report_ticket_id');
      //   $form_state->setUserInput($user_input);
      // }

      $this->sendToZendeskAndValidateNoError($form_state);
    }
  }

  /**
   * Submit ticket update to Zendesk API and validate there were no errors. If an error occurs,
   * fail the webform validation and don't allow the form to be submitted.
   *
   * By submitting to the API during the validate phase, we can interrupt the form submission,
   * prevent the email handlers from firing, and display an error message to the user. Validation
   * in a custom handler is performed after all the built-in webform validation, so this is a
   * safe approach.
   */
  private function sendToZendeskAndValidateNoError(FormStateInterface $form_state)
  {
    if (!$form_state->hasAnyErrors()) {

      // Create a webform submission object from form_state.
      // Since we're doing this in the validate phase, instead of postSave, we need to manually generate
      // a webform_submission object from form_state and pull form values from that for the API submission.

      $webform_submission = $form_state->getFormObject()->getEntity();

      // Get submission fields as array.
      $submission_fields = $webform_submission->toArray(TRUE);

      // Apply filename transformations.
      $this->transformFilenames($submission_fields, $webform_submission);

      // comment out the line below to test the error handling
      $success = $this->sendToZendesk($form_state, $webform_submission);
      if (!$success) {
        // throw error and don't let form submit
        \Drupal::messenger()->addError(t('There was a problem communicating with our support system. Please try again in a few minutes. If the error persists, please <a href="/feedback?subject=The page looks broken&feedback=Report could not be submitted to the support ticketing system.">contact us</a>.'));
        $form_state->setErrorByName('');
      }
    }
  }

  /**
   * Sanitize the filename to remove any unwanted or unsafe characters.
   *
   * @param string $filename
   *   The original filename to sanitize.
   *
   * @return string
   *   The sanitized filename.
   */
  private function sanitizeFilename($filename)
  {
    // Replace unwanted characters with underscores or remove them.
    return preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $filename);
  }

  /**
   * Apply filename transformations manually during validation phase.
   */
  private function transformFilenames(array &$submission_fields, WebformSubmissionInterface $webform_submission, array $elements = [], string $parent_key = '')
  {
    // If no elements are passed, get the full element structure.
    if (empty($elements)) {
      $webform = $webform_submission->getWebform();
      $elements = $webform->getElementsDecoded();
    }

    // Iterate through each element to find those with filename patterns or nested elements.
    foreach ($elements as $element_key => $element) {
      // Construct the full key path for nested elements.
      $full_key = $parent_key ? "{$parent_key}.{$element_key}" : $element_key;

      // Check if the element has a filename pattern.
      if (isset($element['#file_name'])) {
        $filename_pattern = $element['#file_name'];

        // Check if the submission includes files for this element.
        if (!empty($submission_fields['data'][$element_key])) {
          $file_ids = $submission_fields['data'][$element_key];

          // Ensure file IDs are in an array for consistent processing.
          if (!is_array($file_ids)) {
            $file_ids = [$file_ids];
          }

          // Process each file ID.
          foreach ($file_ids as &$file_id) {
            $file = File::load($file_id);

            if ($file) {
              // Get the original filename and its extension.
              $original_filename = $file->getFilename();
              $file_extension = pathinfo($original_filename, PATHINFO_EXTENSION);

              // Sanitize the original filename without the extension.
              $cleaned_filename = $this->sanitizeFilename(pathinfo($original_filename, PATHINFO_FILENAME));

              // Replace tokens in the filename pattern.
              $transformed_filename = \Drupal::token()->replace($filename_pattern, [
                'webform_submission' => $webform_submission,
                'filename' => $cleaned_filename,
              ]);

              // Append the original file extension to the transformed filename.
              $final_filename = $transformed_filename . '.' . $file_extension;

              // Set the transformed filename on the file entity.
              $file->setFilename($final_filename);
              $file->save();

              // Update the file data in the submission fields with the transformed filename.
              $file_id = $file->id();
            }
          }

          // Update the submission fields with the processed file IDs.
          $submission_fields['data'][$element_key] = count($file_ids) === 1 ? reset($file_ids) : $file_ids;
        }
      }

      // Check if the element has children and traverse them recursively.
      if (isset($element['#type']) && in_array($element['#type'], ['container', 'section', 'details', 'fieldset']) && !empty($element['#children'])) {
        $this->transformFilenames($submission_fields, $webform_submission, $element['#children'], $full_key);
      } elseif (is_array($element)) {
        // Recursively process nested arrays to ensure all levels are checked.
        $this->transformFilenames($submission_fields, $webform_submission, $element, $full_key);
      }
    }
  }

  public function sendToZendesk(FormStateInterface $form_state, $webform_submission)
  {
    // NOTE: This will run for both new and update webform submissions, so this handler should only
    // be used on forms that don't allow updating. Otherwise, a new Zendesk ticket will be created
    // on every submit of the form.

    // declare working variables
    $request = [];

    // manually put the report_ticket_id value in the webform submission object, so that it
    // gets used in token replacement and in custom field if needed.
    // NOTE: This will always occur when the ZendeskUpdateHandler is called after ZendeskHandler; the
    // assumption is that if we're updating a ticket right after creating one, they're related.
    $webform_submission->setElementData('report_ticket_id', $form_state->getValue('report_ticket_id'));

    $submission_fields = $webform_submission->toArray(TRUE);
    $configuration = $this->getTokenManager()->replace($this->configuration, $webform_submission);

    // if the update handler is configured to use the ticket id field zendesk_request_number,
    // it's a sub-element of the support agent widget, so it needs to be retrieved differently...
    if ($configuration['ticket_id_field'] == "zendesk_request_number") {
      $zendesk_ticket_id = $submission_fields['data']['support_agent_use_only'][$configuration['ticket_id_field']];
    } else {
      $zendesk_ticket_id = $submission_fields['data'][$configuration['ticket_id_field']];
    }

    $confirm_zendesk_ticket_id = 0; // this will be updated and validated after getting the ticket

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

    if (!isset($request['comment']['body'])) {
      $comment = $request['comment'];
      $request['comment'] = [
        'html_body' => $comment,
        'public' => !$request['comment_private']
      ];
    }

    // convert custom fields format from [key:data} to [id:key,value:data] for Zendesk field referencing
    $custom_fields = Yaml::decode($request['custom_fields']);
    unset($request['custom_fields']);
    $request['custom_fields'] = [];
    if ($custom_fields) {
      foreach ($custom_fields as $key => $value) {
        // KLUGE: this is a kludge to prevent querystring ampersands from being escaped in the resolution_url custom field,
        // which prevents the URL from being usable in Zendesk emails, since it doesn't unescape them in triggers. For the
        // Portland instance, the resolution_url field should always have this key, but other url custom fields may need
        // to be added in the future.
        if ($key == "6355783758871") {
          $value = str_replace("&amp;", "&", $value);
        } // END KLUGE

        $request['custom_fields'][] = [
          'id' => $key,
          'value' => $value
        ];
      }
    }

    // get list of all webform fields with a file field type
    $file_fields = $this->getWebformFieldsWithFiles();

    // attempt to send request to update zendesk ticket
    try {

      // initiate api client
      $client = new ZendeskClient();

      // get existing ticket values
      $ticket = $client->tickets()->find($zendesk_ticket_id)->ticket;

      // Checks for files in submission values and uploads them if found
      foreach ($submission_fields['data'] as $key => $submission_field) {
        if (in_array($key, $file_fields) && !empty($submission_field)) {

          // pack file index/indices into an array for looping
          if (is_array($submission_field)) {
            $file_indices = $submission_field;
          } else {
            $file_indices = []; // clear var
            $file_indices[] = $submission_field;
          }

          // individually attach each uploaded file per file submission_field
          foreach ($file_indices as $file_index) {
            // get file from index for upload
            $file = File::load($file_index);

            // add uploads key to Zendesk comment, if not already present
            if ($file && !array_key_exists('uploads', $request['comment'])) {
              $request['comment']['uploads'] = [];
            }

            // upload file and get response
            $attachment = $client->attachments()->upload([
              'file' => $file->getFileUri(),
              'type' => $file->getMimeType(),
              'name' => $file->getFileName(),
            ]);

            // add upload token to comment
            if ($attachment && isset($attachment->upload->token)) {
              $request['comment']['uploads'][] = $attachment->upload->token;
            }
          }
        }
      }

      // status, type, priority, and group are required by the API, even for update. if they're not set, set them from
      // previous ticket data.
      if (!isset($request['status']) || $request['status'] == "") {
        $request['status'] = $ticket->status;
      }
      if (!isset($request['type']) || $request['type'] == "") {
        $request['type'] = $ticket->type;
      }
      if (!isset($request['priority']) || $request['priority'] == "") {
        $request['priority'] = $ticket->priority;
      }
      // don't send empty group; get it from previous ticket
      if (!isset($request['group_id']) || $request['group_id'] == "") {
        $request['group_id'] = $ticket->group_id;
      }

      // if tags not set, use previous value
      if (!isset($request['tags']) || $request['tags'] == "") {
        $request['tags'] = $ticket->tags;
      }

      // create ticket
      $updated_ticket = $client->tickets()->update($zendesk_ticket_id, $request);
      $confirm_zendesk_ticket_id = $updated_ticket->ticket->id;
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
    }

    return $zendesk_ticket_id == $confirm_zendesk_ticket_id;
  }

  /**
   * Submits a Zendesk ticket once the Webform has been submitted and saved
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {}

  /**
   * Displays a list of configured values on the Handlers page
   * {@inheritdoc}
   */
  public function getSummary()
  {
    $markup = [];
    $configNames = array_keys($this->defaultConfiguration());
    $excluded_fields = [];

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

  /**
   * Token manager setter
   * @param WebformTokenManagerInterface $token_manager
   */
  public function setTokenManager(WebformTokenManagerInterface $token_manager)
  {
    $this->token_manager = $token_manager;
  }

  /**
   * Token manager getter
   * @return WebformTokenManagerInterface
   */
  public function getTokenManager()
  {
    return $this->token_manager;
  }

  /**
   * @return array
   */
  protected function getWebformFieldsWithFiles()
  {
    return $this->getWebform()->getElementsManagedFiles();
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
