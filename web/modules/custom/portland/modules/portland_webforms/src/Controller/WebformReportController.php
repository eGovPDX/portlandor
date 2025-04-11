<?php

namespace Drupal\portland_webforms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Drupal\Core\Url;

class WebformReportController extends ControllerBase {
  private const CACHE_ID = 'portland_webforms:webform_report_cache';

  protected SerializerInterface $serializer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->serializer = $container->get('serializer');

    return $instance;
  }

  /**
   * Generates the webform report in CSV format.
   */
  public function generateCsv() {
    $response = new Response(
      '',
      200,
      [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="webform-report.csv"',
      ]
    );

    // Check if the report is already cached.
    $cache = \Drupal::cache()->get(self::CACHE_ID);
    if ($cache && !empty($cache->data)) {
      $response->setContent($cache->data);
    } else {
      // Generate the report data.
      $webforms = Webform::loadMultiple();
      $rows = [];

      foreach ($webforms as $webform) {
        $elements = $webform->getElementsInitializedFlattenedAndHasValue(); // Decode the elements.
        // Extract the custom field value from the webform handler.
        $custom_field_value = $this->getCustomFieldValue($webform, '6353388345367');
        // Find entities referencing this webform.
        //$embedded_locations = $this->getEmbeddedLocations($webform->id());

        $common_columns = [
          'Webform ID' => $webform->id(),
          'Webform ID in Zendesk' => $custom_field_value,
          'Webform Title' => $webform->label(),
        ];

        foreach ($elements as $key => $element) {
          $rows[] = [
            ...$common_columns,
            'Element Key' => $key,
            'Element Title' => trim(strip_tags($element['#title'])) ?? '',
            'Element Type' => $element['#type'] ?? 'Unknown',
            'Required' => !empty($element['#required']) ? 'Yes' : 'No',
            //'Embedded Location' => implode(', ', $embedded_locations), // Embedded locations
          ];

          if (array_key_exists('#webform_composite_elements', $element)) {
            foreach ($element['#webform_composite_elements'] as $composite_key => $composite_element) {
              if (isset($composite_element['#webform_composite_key'])) {
                $rows[] = [
                  ...$common_columns,
                  'Element Key' => $key . ':' . $composite_key,
                  'Element Title' => trim(strip_tags($composite_element['#title'])) ?? '',
                  'Element Type' => $composite_element['#type'] ?? 'Unknown',
                  'Required' => !empty($composite_element['#required']) ? 'Yes' : 'No',
                ];
              }
            }
          }
        }
      }

      // Generate CSV content.
      $csvContent = $this->serializer->serialize($rows, 'csv');
      $response->setContent($csvContent);
      // Cache the generated CSV content for 1 hour.
      \Drupal::cache()->set(self::CACHE_ID, $csvContent, time() + 3600);
    }

    return $response;
  }

  /**
   * Generates a CSV report of all webforms with their details.
   */
  public function generateFormsCsv() {
    $response = new Response(
      '',
      200,
      [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="webform-forms-report.csv"',
      ]
    );

    // Check if the report is already cached.
    $cache = \Drupal::cache()->get(self::CACHE_ID . ':forms');
    if ($cache && !empty($cache->data)) {
      $response->setContent($cache->data);
    } else {
      // Generate the report data.
      $webforms = Webform::loadMultiple();
      $rows = [];

      foreach ($webforms as $webform) {
        $handlers = $webform->getHandlers();
        $handler_types = [];
        foreach ($handlers as $handler) {
          $handler_types[] = $handler->getPluginId();
        }

        // Generate the webform's URL.
        $webform_url = Url::fromRoute('entity.webform.canonical', ['webform' => $webform->id()], ['absolute' => TRUE])->toString();

        $rows[] = [
          'Webform ID' => $webform->id(),
          'Webform Title' => $webform->label(),
          'Admin Description' => $webform->get('description') ?? 'No description',
          'Number of Fields' => count($webform->getElementsInitializedFlattenedAndHasValue()),
          'Number of Handlers' => count($handlers),
          'Handler Types' => implode(', ', $handler_types),
          'Webform URL' => $webform_url,
        ];
      }

      // Generate CSV content.
      $csvContent = $this->serializer->serialize($rows, 'csv');
      $response->setContent($csvContent);
      // Cache the generated CSV content for 1 hour.
      \Drupal::cache()->set(self::CACHE_ID . ':forms', $csvContent, time() + 3600);
    }

    return $response;
  }

  /**
   * Gets the value of a specific custom field from the webform handler configuration.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform entity.
   * @param string $field_id
   *   The custom field ID to extract.
   *
   * @return string
   *   The value of the custom field, or 'Not configured' if not found.
   */
  protected function getCustomFieldValue(Webform $webform, $field_id) {
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      $configuration = $handler->getConfiguration();
      if (isset($configuration['settings']['custom_fields'])) {
        $custom_fields = $configuration['settings']['custom_fields'];

        // Safely parse the custom_fields YAML or JSON into an array.
        try {
          $custom_fields_array = \Drupal\Component\Serialization\Yaml::decode($custom_fields);
        } catch (\Exception $e) {
          \Drupal::logger('webform_report')->error('Failed to parse custom fields: @error', ['@error' => $e->getMessage()]);
          continue;
        }

        // Check if the desired field exists.
        if (isset($custom_fields_array[$field_id])) {
          $value = $custom_fields_array[$field_id];

          // Handle single values or arrays.
          if (is_array($value)) {
            return implode(', ', $value); // Convert array to string.
          }
          return $value;
        }
      }
    }

    return '';
  }

}
