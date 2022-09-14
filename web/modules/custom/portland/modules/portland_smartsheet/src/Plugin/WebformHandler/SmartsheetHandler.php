<?php
/**
 * Created by PhpStorm.
 * User: Steven
 * Date: 2019-05-18
 * Time: 10:05 AM
 */

namespace Drupal\portland_smartsheet\Plugin\WebformHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Drupal\portland_smartsheet\Client\SmartsheetClient;
use Drupal\Component\Serialization\Json;


/**
 * Form submission to Smartsheet handler.
 *
 * @WebformHandler(
 *   id = "smartsheet_add_row",
 *   label = @Translation("Smartsheet: add row to existing sheet"),
 *   category = @Translation("Smartsheet"),
 *   description = @Translation("Adds a row with webform submission data to an existing Smartsheet"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class SmartsheetHandler extends WebformHandlerBase {
  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $token_manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->token_manager = $container->get('webform.token_manager');
    return $instance;
  }

  /**
   * Provides list of setting fields
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'column_mappings' => [],
      'sheet_id' => '',
      'submission_id_column' => '',
    ];
  }

  /**
   * @return array
   */
  public function defaultConfigurationNames() {
    return array_keys($this->defaultConfiguration());
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['sheet_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sheet ID'),
      '#description' => $this->t('Smartsheet sheet ID to use'),
      '#default_value' => $this->configuration['sheet_id'],
      '#required' => true
    ];

    $form['submission_id_column'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submission ID column'),
      '#description' => $this->t('Column to put submission ID in, if any'),
      '#default_value' => $this->configuration['submission_id_column'],
      '#required' => false
    ];

    try {
      if (!$this->configuration['sheet_id']) return;
      $client = new SmartsheetClient($this->configuration['sheet_id']);

      $columns = $client->listAllColumns();
      $form['column_mappings'] = [
        '#type' => 'details',
        '#title' => $this->t('Column mappings'),
        'table' => [
          '#type' => 'table',
          '#header' => [
            $this->t('Smartsheet column'),
            $this->t('Webform field'),
          ],
          '#rows' => array_map(fn($col) => [
            ['data' => ['#markup' => "<strong>{$col->title}</strong>", '#value' => $col->id]],
            ['data' => ['#type' => 'select', '#options' => ['' => 'None', '1' => "Two"]]]
          ], $columns),
        ]
      ];
    } catch (\Exception $e) {
      // Log error message.
      $this->getLogger()->error('@form fetching columns from Smartsheet failed. @exception: @message.', [
        '@exception' => get_class($e),
        '@form' => $this->getWebform()->label(),
        '@message' => $e->getMessage(),
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ]);
    }



    $form['column_mapping'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Column mapping'),
      '#help' => $this->t('Key (field id) -> Smartsheet column id'),
      '#description' => $this->t(
        '<div id="help">If a field is not listed, it will be ignored</div>'
      ),
      '#default_value' => $this->configuration['column_mapping'],
      '#description_display' => 'before',
      '#weight' => 90,
      '#attributes' => [
        'placeholder' => 'email: 4820834508'
      ],
      '#required' => false,
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * Saves handler settings to config
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $submission_value = $form_state->getValues();
    foreach ($this->configuration as $key => $value) {
      if (isset($submission_value[$key])) {
        $this->configuration[$key] = $submission_value[$key];
      }
    }
  }

  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $submission_fields = $webform_submission->toArray(TRUE);

    $column_mapping = Yaml::decode($this->configuration['column_mapping']);
    try {
      $client = new SmartsheetClient($this->configuration['sheet_id']);

      $cells = [];
      foreach ($submission_fields['data'] as $key => $submission_field) {
        if (!$column_mapping[$key]) continue;

        array_push($cells, [
          'columnId' => (int) $column_mapping[$key],
          'value' => is_array($submission_field) ? join(",", $submission_field) : $submission_field
        ]);
      }

      if ($this->configuration['submission_id_column']) {
        array_push($cells, [
          'columnId' => (int) $this->configuration['submission_id_column'],
          'value' => $submission_fields["sid"] !== "" ? $submission_fields["sid"] : $submission_fields["uuid"]
        ]);
      }

      $response_data = $client->addRow([
        'cells' => $cells
      ]);
    } catch (\Exception $e) {
      // Log error message.
      $this->getLogger()->error('@form webform submission to Smartsheet failed. @exception: @message.', [
        '@exception' => get_class($e),
        '@form' => $this->getWebform()->label(),
        '@message' => $e->getMessage(),
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ]);
    }
  }
}
