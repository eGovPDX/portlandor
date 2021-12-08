<?php

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


/**
 * Form submission to Zendesk to update a ticket.
 *
 * @WebformHandler(
 *   id = "zendesk_update_ticket",
 *   label = @Translation("Zendesk update ticket"),
 *   category = @Translation("Zendesk"),
 *   description = @Translation("Updates an existing Zendesk support ticket."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 *
 * This handler needs to do the following:
 * - Allow specifying which webform field holds the zendesk ticket ID
 * - Retrieve the ticket identified in the field using $client->tickets()->find($id); 
 * - Update ticket: https://developer.zendesk.com/api-reference/ticketing/tickets/tickets/#update-ticket
 * - Update tag lists: https://developer.zendesk.com/api-reference/ticketing/tickets/tickets/#updating-tag-lists
 * - How do we determine which fields we need to update?
 *    - Need two fields: Ticket Fields and Ticket Custom Fields
 *    - Use the Ticket Custom Fields field in the handler config
 *    - Use Drupal placeholders to add values
 *    - Only what's listed here gets updated
 * - On load of the handler config form, retrieve the ticket object using the api
 *    - Make the ticket structure viewable in the UI (like the custom Field Reference accordion?)
 * 
 * - General tab, remove:
 *    - Subject - REMOVE
 *    - Ticket Body - Change to comment
 *    - Ticket CCs - REMOVE
 *    - Requester Name - REMOVE
 *    - Requester Email Address - REMOVE
 *    - Ticket Type - REMOVE, can be included in texy field list
 *    - Ticket Priority - REMOVE, can be included in texy field list
 *    - Ticket Assignee - REMOVE, can be included in texy field list
 * 
 * Fields from ticket object we might want to populate:
 *  type
 *  priority
 *  status
 *  assignee_id
 *  zendesk ticket id field
 *  ticket tags
 *  custom_fields
 *  comment - it looks like setting the comment property on ticket update json will add it to the thread
 *    https://developer.zendesk.com/api-reference/ticketing/tickets/tickets/?_ga=2.3553525.417292175.1637041656-1860072951.1623776472#example-body-2
 *  tokens link
 * 
 * TODO: What kind of validation can we put on the resolution requests? The company agent might mess up the URL and close the wrong ticket.
 * 
 */
class ZendeskUpdateHandler extends WebformHandlerBase
{

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
            'comment' => '',
            'tags' => '',
            'priority' => '',
            'status' => '',
            'assignee_id' => '',
            'type' => '',
            'collaborators' => '',
            'custom_fields' => '',
            'ticket_id_field' => '',
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

        // 

        $assignees = [];

        try {
            // get available assignees from zendesk
            // initiate api client
            $client = new ZendeskClient();

            // get list of all users who are either agents or admins
            $response_agents = $client->users()->findAll([ 'role' => 'agent' ]);
            $response_admins = $client->users()->findAll([ 'role' => 'admin' ]);
            $users = array_merge( $response_agents->users, $response_admins->users );

            // store found agents
            foreach($users as $user){
                $assignees[ $user->id ] = $user->name;
            }

            // order agents by name
            asort($assignees);

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

        }
        catch( \Exception $e ){
            // Encode HTML entities to prevent broken markup from breaking the page.
            $message = nl2br(htmlentities($e->getMessage()));

            // Log error message.
            $this->getLogger()->error('Retrieval of assignees for @form webform Zendesk handler failed. @exception: @message. Click to edit @link.', [
                '@exception' => get_class($e),
                '@form' => $this->getWebform()->label(),
                '@message' => $message,
                'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
            ]);
        }

        // build form fields
        // TODO: Figure out why the values won't save if we use a ['settings'] container to hold the config fields.
        //       Natural and weighted sorting does't work correctly if the container is used.
        //       $form['settings']['intro_text'] = [ ... ];

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

        // prep assignees field
        // if found assignees from Zendesk, populate dropdown.
        // otherwise provide field to specify assignee ID
        $form['assignee_id'] = [
            '#title' => $this->t('Ticket Assignee'),
            '#description' => $this->t('Select an assignee for the updated ticket or enter an email address (must be a Zendesk user). Or select None to leave the ticket assigned to the current assignee.'),
            '#default_value' => $this->configuration['assignee_id'],
            '#required' => false
        ];
        if(!empty($assignees) ){
            $form['assignee_id']['#type'] = 'webform_select_other';
            $form['assignee_id']['#options'] = $assignees;
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

        $form['comment'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Ticket Comment'),
            '#description' => $this->t('Use this field to add a comment to every ticket updated by this webform. A comment notification will be sent to the original requester.'),
            '#default_value' => $this->configuration['comment'],
            '#format' => 'full_html'
        ];

        $form['custom_fields'] = [
            '#type' => 'webform_codemirror',
            '#mode' => 'yaml',
            '#title' => $this->t('Ticket Custom Fields'),
            '#help' => $this->t('Custom form fields for the ticket'),
            '#description' => $this->t(
                '<div id="help">To set the value of one or more custom fields in the new Zendesk ticket, in <a href="https://learn.getgrav.org/16/advanced/yaml#mappings" target="_blank">YAML format</a>, specify a list of pairs consisting of IDs and values. You may find the custom field ID when viewing the list of <a href="https://'.$zendesk_subdomain.'.zendesk.com/agent/admin/ticket_fields" target="_blank">Ticket Fields</a> in Zendesk, or by clicking "<strong>Field Reference</strong>" below for a list of available fields. Values may be plain text, or Drupal webform tokens/placeholders. <p class="">Eg. <code class="CodeMirror"><span>12345678</span>: <span>\'foobar\'</span></code></p> </div>'
            ),
            '#default_value' => $this->configuration['custom_fields'],
            '#description_display' => 'before',
            '#attributes' => [
                'placeholder' => '146455678: \'[webform_submission:value:email]\''
            ],
            '#required' => false,
            '#more_title' => 'Field Reference',
            '#more' => '<div class="zd-ticket-reference">' . Utility::convertTable($form_ticket_fields) .'</div>',
        ];

        // display link for token variables
        $form['token_link'] = $this->getTokenManager()->buildTreeLink();

        return parent::buildConfigurationForm($form, $form_state);
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
        foreach($this->configuration as $key => $value){
            if(isset($submission_value[$key])){
                $this->configuration[$key] = $submission_value[$key];
            }
        }
    }

    /**
     * Submits a Zendesk ticket once the Webform has been submitted and saved
     * {@inheritdoc}
     */
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE)
    {
      //return false;

      // declare working variables
      $request = [];
      $submission_fields = $webform_submission->toArray(TRUE);
      $configuration = $this->getTokenManager()->replace($this->configuration, $webform_submission);
      $zendesk_ticket_id = $submission_fields['data'][$configuration['ticket_id_field']];

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

      if(!isset($request['comment']['body'])){
        $comment = $request['comment'];
        $request['comment'] = [
            'html_body' => $comment, 'public' => true
        ];
      }

      // convert custom fields format from [key:data} to [id:key,value:data] for Zendesk field referencing
      $custom_fields = Yaml::decode($request['custom_fields']);
      unset($request['custom_fields']);
      $request['custom_fields'] = [];
      if($custom_fields) {
        foreach ($custom_fields as $key => $value) {
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

          // type and priority are required by the API, even for update. if they're not set, set them from
          // previous ticket data.
          if (!isset($request['type']) || $request['type'] == "") {
            $request['type'] = $ticket->type;
          }
          if (!isset($request['priority']) || $request['priority'] == "") {
            $request['priority'] = $ticket->priority;
          }

          // create ticket
          $updated_ticket = $client->tickets()->update($zendesk_ticket_id, $request);

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
      return;
    }

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