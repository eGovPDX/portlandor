<?php
/**
 * Created by PhpStorm.
 * User: Steven
 * Date: 2019-05-18
 * Time: 10:05 AM
 */

namespace Drupal\portland_zendesk\Plugin\WebformHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\portland_zendesk\Client\ZendeskClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\portland_zendesk\Utils\Utility;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;


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
  private const JSON_FORM_DATA_FIELD_ID = 17698062540823;

  /**
   * @var WebformTokenManagerInterface $token_manager
   */
  protected $token_manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    /**
     * @var WebformTokenManagerInterface $webform_token_manager
     */

    $static = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $webform_token_manager = $container->get('webform.token_manager');
    $static->setTokenManager( $webform_token_manager );

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
      'collaborators' => '',
      'custom_fields' => '',
      'ticket_id_field' => '',
      'ticket_fork_field' => '',
      'ticket_form_id' => '',
    ];
  }

  /**
   * @return array
   */
  public function defaultConfigurationNames()
  {
    return array_keys( $this->defaultConfiguration() );
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
      foreach($webform_fields as $key => $field){
        if( Utility::checkIsGroupingField($field) ){
          foreach($field as $subkey => $subfield){
            if(!preg_match("/^#/",$subkey) && isset($subfield['#type'])) {
              if (Utility::checkIsEmailField($subfield)) {
                $options['email'][$subkey] = $subfield['#title'];
              }
              elseif (Utility::checkIsNameField($subfield)) {
                $options['name'][$subkey] = $subfield['#title'];
              }
              elseif (Utility::checkIsHiddenField($subfield)) {
                $options['hidden'][$subkey] = $subfield['#title'];
              }
            }
          }
        }
        else{
          if( Utility::checkIsEmailField($field) ){
            $options['email'][$key] = $field['#title'];
          }
          elseif( Utility::checkIsNameField($field) ){
            $options['name'][$key] = $field['#title'];
          }
          elseif( Utility::checkIsHiddenField($field) ){
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
        foreach($response_groups->groups as $group){
          $groups[ $group->id ] = $group->name;
        }
        // order groups by name
        asort($groups);

        // get list of all admin and agent users to populate assignee field
        $response_agents = $client->users()->findAll([ 'role' => 'agent' ]);
        $response_admins = $client->users()->findAll([ 'role' => 'admin' ]);
        $users = array_merge( $response_agents->users, $response_admins->users );

        // store found agents
        foreach($users as $user){
          $assignees[ $user->id ] = $user->name;
        }

        // order agents by name
        asort($assignees);

        // get list of recipeint addresses
        $response_recipients = $client->supportAddresses()->findAll();
        foreach ($response_recipients->recipient_addresses as $recipient) {
          $recipients[ $recipient->email] = $recipient->email;
        }
        asort($recipients);

        // get list of ticket fields and assign them to an array by id->title
        $response_fields = $client->ticketFields()->findAll();

        if( $response_fields->ticket_fields ) {
            foreach($response_fields->ticket_fields as $field) {
                // exclude system ticket fields and inactive fields
                if( !in_array($field->title,$form_field_exclusions) && $field->active ) {
                    $form_ticket_fields[$field->id] = $field->title;
                }
            }
        }

        // order ticket fields by name
        asort($form_ticket_fields);

        // Get all active ticket forms from Zendesk
        $ticket_forms = $client->get("ticket_forms?active=true")->ticket_forms;
      }
      catch( \Exception $e ){
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
      if(!empty($recipients) ){
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
      if(!empty($groups) ){
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
      if(! empty($assignees) ){
        $form['assignee_id']['#type'] = 'select';
        $form['assignee_id']['#options'] = ['' => '- None -'] + $assignees;
        $form['assignee_id']['#description'] = $this->t('The assignee to which the ticket should be assigned. Set either Ticket Group or Ticket Assignee, but not both. Typically tickets created by webforms should not be assigned to individual users, but tickets that are created as Solved must have an individual assignee. In this case, use the Portland.gov Support service account.');
      }
      else {
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
      if(!empty($ticket_forms) ){
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
          You may find the custom field ID when viewing the list of <a href=\"https://${zendesk_subdomain}.zendesk.com/agent/admin/ticket_fields\" target=\"_blank\">Ticket Fields</a> in Zendesk, or by clicking <strong>Field Reference</strong>
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
        '#more' => '<div class="zd-ticket-reference">' . Utility::convertTable($form_ticket_fields) .'</div>',
      ];

      // display link for token variables
      $form['token_link'] = $this->getTokenManager()->buildTreeLink();

      $form['ticket_id_field'] = [
        '#type' => 'webform_select_other',
        '#title' => $this->t('Zendesk Ticket ID Field'),
        '#description' => $this->t('The name of hidden field which will be updated with the created Ticket ID.'),
        '#default_value' => $this->configuration['ticket_id_field'],
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

      return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Saves handler settings to config
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitConfigurationForm($form, $form_state);

    $submission_value = $form_state->getValues();
    foreach($this->configuration as $key => $value){
      if(isset($submission_value[$key])){
        $this->configuration[$key] = $submission_value[$key];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
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
  private function sendToZendeskAndValidateTicket(array &$form, FormStateInterface $form_state) {
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

  public function sendToZendesk(array &$form, FormStateInterface &$form_state) {
    // NOTE: This function will run both when a webform is created, and when it's updated, so this handler
    // should only be used on forms that don't allow updating. Otherwise, a new Zendesk ticket will be created
    // on every submit of the form.

    $new_ticket_id = 0;
    $zendesk_ticket_id_field_name = $this->configuration['ticket_id_field'];

    // tickets will be forked on the field identified in the config value 'ticket_fork_field'
    $fork_field_name = $this->configuration['ticket_fork_field'];

    // Since we're doing this in the validate phase, instead of postSave, we need to manually generate
    // a webform_submission object from form_state and pull form values from that for the API submission.
    $webform_submission = $form_state->getFormObject()->getEntity();

    if ($fork_field_name) {
      // if the handler has a fork field configured, grab the values array from that field so we can
      // spin through it and stuff a single value into the webform_submission for each ticket being created.

      $data = $webform_submission->getData();
      $fork_field_array = $data[$fork_field_name];  // TODO: inefficient; improve?
      $ticket_ids = [];

      foreach ($fork_field_array as $key => $value) {
        $data[$fork_field_name] = $fork_field_array[$key];
        $webform_submission->setData($data);
        $submission_fields = $webform_submission->toArray(TRUE);
        $configuration = $this->getTokenManager()->replace($this->configuration, $webform_submission);

        // call function to create ticket in Zendesk and store resulting ticket ID
        $ticket_id = $this->submitTicket($submission_fields, $configuration, $webform_submission->id());
        // if ticket ID = 0, there was an error, so exit out and make the form error
        if ($ticket_id === 0) return null;

        $ticket_ids[] = $ticket_id;
      }

      // put original data back in webform_submission? no, that doesn't help.
      // for some reason, the vehicle info is missing altogether in the forked tickets.
      // $data[$fork_field_name] = $fork_field_array;
      // $webform_submission->setData($this->getTokenManager()->replace($data));

      $new_ticket_id = implode(",", $ticket_ids);

    } else {
      $submission_fields = $webform_submission->toArray(TRUE);
      $configuration = $this->getTokenManager()->replace($this->configuration, $webform_submission);
      $new_ticket_id = $this->submitTicket($submission_fields, $configuration, $webform_submission->id());
      $data = $webform_submission->getData();
    }

    // if name field is set and present, add ticket ID to hidden Zendesk Ticket ID field
    if ($zendesk_ticket_id_field_name && array_key_exists( $zendesk_ticket_id_field_name, $data ) && $new_ticket_id){
      $data[$zendesk_ticket_id_field_name] = $new_ticket_id;
      $form_state->setValue($zendesk_ticket_id_field_name, $new_ticket_id);
      $form['values'][$zendesk_ticket_id_field_name] = $new_ticket_id;
    }

    return $new_ticket_id; // if a null is returned, an error/try-again message will be displayed to the user
  }

  public function submitTicket($submission_fields, $configuration, $webform_submission_id) {
    $new_ticket_id = 0;
    $request = [];

    // Allow for either values coming from other fields or static/tokens
    foreach ($this->defaultConfigurationNames() as $field) {
      $request[$field] = $configuration[$field];
      if (!empty($submission_fields['data'][$configuration[$field]])) {
        $request[$field] = $submission_fields['data'][$configuration[$field]];
      }
    }

    // clean up tags
    $request['tags'] = Utility::cleanTags( $request['tags'] );
    $request['collaborators'] = preg_split("/[^a-z0-9_\-@\.']+/i", $request['collaborators'] );
    if (!empty($request['ticket_form_id'])) $request['ticket_form_id'] = $this->configuration['ticket_form_id'];

    // restructure requester
    if(!isset($request['requester'])){
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
    if(!isset($request['comment']['body'])){
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

        // KLUGE: this is a kludge to prevent querystring ampersands from being escaped in the resolution_url custom field,
        // which prevents the URL from being usable in Zendesk emails, since it doesn't unescape them in triggers. For the
        // Portland instance, the resolution_url field should always have this key, but other url custom fields may need
        // to be added in the future.
        if ($key == "6355783758871") {
          $value = str_replace("&amp;", "&", $value);
          $value = str_replace("&#039;", '\'', $value);
        } // END KLUGE

        // NEXT KLUGE: If the token is empty, it's not getting replaced, and the token code is appearing in custom fields
        // in Zendesk. also, the default filter isn't working. so we'll just clear out any values that start with a token
        // string. at present, custom values will only include the tokens, not additional string values, so this approach
        // is fine for now but may need to be extended to remove the token code from within a longer string.
        if (substr($value, 0, 27) == "[webform_submission:values:") {
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
      array_filter($custom_fields, fn ($val) => is_array($val) && count($val) === 2),
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
    $request['external_id'] = $webform_submission_id;

    // get list of all webform fields with a file field type
    $file_fields = $this->getWebformFieldsWithFiles();

    // attempt to send request to create zendesk ticket
    try {
      // initiate api client
      $client = new ZendeskClient();

      // Checks for files in submission values and uploads them if found
      foreach($submission_fields['data'] as $key => $submission_field){
        if( in_array($key, $file_fields) && !empty($submission_field) ){

          // pack file index/indices into an array for looping
          if( is_array( $submission_field ) ){
              $file_indices = $submission_field;
          } else {
              $file_indices = []; // clear var
              $file_indices[] = $submission_field;
          }

          // individually attach each uploaded file per file submission_field
          foreach( $file_indices as $file_index) {
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

      // create ticket
      $new_ticket = $client->tickets()->create($request);

      $new_ticket_id = $new_ticket->ticket->id;

    }
    catch( \Exception $e ){

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

    return $new_ticket_id;

  }

  /**
   * Displays a list of configured values on the Handlers page
   * {@inheritdoc}
   */
  public function getSummary()
  {
    $markup = [];
    $configNames = array_keys($this->defaultConfiguration());
    $excluded_fields = ['comment','custom_fields'];

    // loop through fields to display an at-a-glance summary of settings
    foreach($configNames as $configName){
      if(! in_array($configName, $excluded_fields) ) {
        $markup[] = '<strong>' . $this->t($configName) . ': </strong>' . ($this->configuration[$configName]);
      }
    }

    return [
      '#markup' => implode('<br>',$markup),
    ];
  }

  /**
   * Token manager setter
   * @param WebformTokenManagerInterface $token_manager
   */
  public function setTokenManager( WebformTokenManagerInterface $token_manager ){
    $this->token_manager = $token_manager;
  }

  /**
   * Token manager getter
   * @return WebformTokenManagerInterface
   */
  public function getTokenManager(){
    return $this->token_manager;
  }

  /**
   * @return array
   */
  protected function getWebformFieldsWithFiles(){
    return $this->getWebform()->getElementsManagedFiles();
  }

  // Deprecated functions

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsNameField( array $field ){
    return Utility::checkIsNameField($field);
  }

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsEmailField( array $field ){
    return Utility::checkIsEmailField($field);
  }

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsHiddenField( array $field ){
    return Utility::checkIsHiddenField($field);
  }

  /**
   * @param array $field
   * @return bool
   * @deprecated
   */
  protected function checkIsGroupingField( array $field ){
    return Utility::checkIsGroupingField($field);
  }

  /**
   * @param string $text
   * @return string
   * @deprecated
   */
  protected function cleanTags( $text = '' ){
    return Utility::cleanTags($text);
  }

  /**
   * @param string $text
   * @return string
   * @deprecated
   */
  protected function convertTags( $text = '' ){
    return Utility::convertTags($text);
  }

  /**
   * @param string $text
   * @return string
   * @deprecated
   */
  protected function convertName( $name_parts ){
    return Utility::convertName($name_parts);
  }

  /**
   * @param array $text
   * @return string
   * @deprecated
   */
  protected function convertTable( $set ){
    return Utility::convertTable($set);
  }
}
